test:
	docker run --name some-postgres -e POSTGRES_PASSWORD=mysecretpassword -p 5432:5432 -d postgres || true
	bash -c "docker run --name doctrine-psql-enum-test --rm -i -v $$(pwd):/app composer:latest test"

clean:
	docker stop some-postgres || true
	docker rm some-postgres || true
	docker rm doctrine-psql-enum-test || true
