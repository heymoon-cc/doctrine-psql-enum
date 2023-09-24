test:
	bash -c "docker run --rm -i -v $$(pwd):/app composer:latest test"
