network.create:
	docker network create psql-net || true

test: network.create
	docker run --name some-postgres -e POSTGRES_PASSWORD=mysecretpassword --network psql-net -d postgres || true
	bash -c "docker run --name doctrine-psql-enum-test --rm -i --network psql-net -v $$(pwd):/app $$(docker build -q .) test" || true

clean:
	docker stop some-postgres || true
	docker rm some-postgres || true
	docker rm doctrine-psql-enum-test || true

network.clear: clean
	docker network remove psql-net || true
