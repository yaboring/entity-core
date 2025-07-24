
## Web API with local state and background tick

## Requirements
- Docker
- Rust (if building/running natively)

### start up database dependency
    docker run --name yaboring-db -e MYSQL_ROOT_PASSWORD=password -p 3306:3306 mysql

### populate database with test content

    CREATE DATABASE yaboring;

    CREATE TABLE yaboring.`entity_types` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
        `description` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    INSERT INTO yaboring.entity_types (title, description) VALUES
	 ('factory','Produces goods'),
	 ('warehouse','Stores goods'),
	 ('truck','Transports goods'),
	 ('client','Receives goods');

    CREATE TABLE yaboring.entities (
        id BIGINT UNSIGNED auto_increment NOT NULL,
        `type` BIGINT UNSIGNED NOT NULL,
        status ENUM('inactive','active') DEFAULT 'inactive' NOT NULL,
        self_governed BOOL DEFAULT 0 NOT NULL,
        CONSTRAINT entities_pk PRIMARY KEY (id),
        CONSTRAINT entities_entity_types_FK FOREIGN KEY (`type`) REFERENCES yaboring.entity_types(id)
    )
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    INSERT INTO yaboring.entities (`type`, status, self_governed) VALUES
	 ((SELECT id from entity_types where title = 'factory'),'active',1),
	 ((SELECT id from entity_types where title = 'warehouse'),'active',1),
	 ((SELECT id from entity_types where title = 'truck'),'active',1),
	 ((SELECT id from entity_types where title = 'client'),'active',1);

### run the code natively
    DATABASE_URL=mysql://root:password@localhost:3306/yaboring YABORING_ENTITY_ID=1 cargo run --release

### run the code in a docker container
    docker build ./ -t yaboring-entity-core:v0.1

    docker run --rm --name yaboring-entity-1-core -p 8081:8080 -e DATABASE_URL=mysql://root:password@host.docker.internal:3306/yaboring -e YABORING_ENTITY_ID=1 yaboring-entity-core:v0.1

    docker run --rm --name yaboring-entity-2-core -p 8082:8080 -e DATABASE_URL=mysql://root:password@host.docker.internal:3306/yaboring -e YABORING_ENTITY_ID=2 yaboring-entity-core:v0.1