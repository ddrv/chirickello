start:
	docker-compose up -d

stop:
	docker-compose stop

api-sh:
	docker-compose exec api sh