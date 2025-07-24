FROM rust:1.88-slim
WORKDIR /usr/src/myapp
COPY . .
RUN cargo build --release
CMD ["cargo", "run", "--release"]
