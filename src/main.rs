use actix_web::{App, HttpServer, Responder, get, web};
use sqlx::Row;
use sqlx::mysql::MySqlPool;
use std::env;
use std::sync::{Arc, Mutex};
use tokio::time::{Duration, interval};

struct EntityState {
    entity_id: Mutex<u64>,
    entity_type_title: Mutex<String>,
    entity_type_description: Mutex<String>,
    heartbeat_count: Mutex<u64>,
}

#[get("/")]
async fn greet(data: web::Data<Arc<EntityState>>) -> impl Responder {
    let entity_id = data.entity_id.lock().unwrap();
    let entity_type_title = data.entity_type_title.lock().unwrap();
    let entity_type_description = data.entity_type_description.lock().unwrap();
    let heartbeat_count = data.heartbeat_count.lock().unwrap();

    return format!(
        "I am entity #{entity_id}. I am a {entity_type_title}, and my job is {entity_type_description}. Background tick count: {heartbeat_count}"
    );
}

#[actix_web::main] // or #[tokio::main]
async fn main() -> std::io::Result<()> {
    println!("connecting to database");

    let database_url = env::var("DATABASE_URL")
        .expect("DATABASE_URL should be set")
        .trim()
        .to_string();

    let pool = MySqlPool::connect(&database_url)
        .await
        .expect("failed to connect to the database");

    println!("connected to database");

    let entity_id = env::var("YABORING_ENTITY_ID")
        .expect("YABORING_ENTITY_ID should be set")
        .trim()
        .parse::<u64>()
        .expect("YABORING_ENTITY_ID should be a number");

    println!("grabbing remote entity state");

    let entity_data = sqlx::query(
        "
        SELECT e.id, et.title, et.description
        FROM entities e
        JOIN entity_types et ON et.id = e.`type`
        WHERE e.id = ?",
    )
    .bind(entity_id)
    .fetch_one(&pool)
    .await
    .expect("failed to fetch entity data");

    let title: String = entity_data.get("title");
    let description: String = entity_data.get("description");

    let entity_state = web::Data::new(Arc::new(EntityState {
        entity_id: Mutex::new(entity_id),
        entity_type_title: Mutex::new(title),
        entity_type_description: Mutex::new(description),
        heartbeat_count: Mutex::new(0),
    }));

    println!("spawning background ticker");

    let ticker_state = entity_state.clone();
    tokio::spawn(async move {
        let mut ticker = interval(Duration::from_secs(1));
        loop {
            ticker.tick().await;
            let mut count = ticker_state.heartbeat_count.lock().unwrap();
            *count += 1;
            println!("background tick: {}", *count);
        }
    });

    println!("starting HTTP server at http://0.0.0.0:8080");

    HttpServer::new(move || App::new().app_data(entity_state.clone()).service(greet))
        .bind(("0.0.0.0", 8080))?
        .run()
        .await
}
