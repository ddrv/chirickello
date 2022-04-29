build:
	if [ ! -f .env ]; then cp .env.example .env; fi
	docker-compose build

start:
	docker-compose up -d

stop:
	docker-compose stop

create-user:
	docker-compose exec auth sh -c "php /opt/app/bin/create-user.php"

create-random-users:
	docker-compose exec auth sh -c "php /opt/app/bin/create-random-users.php"

api-sh:
	docker-compose exec api sh

auth-sh:
	docker-compose exec auth sh