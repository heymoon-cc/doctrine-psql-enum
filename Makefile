network.create:
	docker network create psql-net || podman network create psql-net || true

test: network.create
	docker run --name some-postgres -e POSTGRES_PASSWORD=mysecretpassword --network psql-net -d postgres || \
	podman run --name some-postgres --publish 5432:5432/tcp -e POSTGRES_PASSWORD=mysecretpassword --network psql-net -d docker.io/postgres || true
	bash -c "docker run --name doctrine-psql-enum-test --rm -i --network psql-net -v $$(pwd):/app $$(docker build -q .) test" || \
	bash -c "podman run --name doctrine-psql-enum-test --rm -i --network psql-net -v $$(pwd):/app:Z $$(podman build -q .) test -vvv" || true

clean:
	docker stop some-postgres || podman stop some-postgres || true
	docker rm some-postgres || podman rm some-postgres || true
	docker rm doctrine-psql-enum-test || podman rm doctrine-psql-enum-test || true

network.clear: clean
	docker network remove psql-net || true
