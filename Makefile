build:
	if [ ! -f .env ]; then cp .env.example .env; fi
	if [ ! -d var/log/app ]; then mkdir -p var/log/app; fi
	if [ ! -f var/log/app/app.log ]; then touch var/log/app/app.log; fi
	docker-compose build

start:
	docker-compose up -d

stop:
	docker-compose stop

log:
	docker-compose exec task-tracker sh -c "tail -f /var/log/app/app.log"

create-user:
	docker-compose exec auth sh -c "php /opt/app/bin/create-user.php"

create-random-users:
	docker-compose exec auth sh -c "php /opt/app/bin/create-random-users.php"

get-current-time:
	docker-compose exec accounting sh -c "php /opt/app/bin/get-current-time.php"

auth-sh:
	docker-compose exec auth sh

accounting-sh:
	docker-compose exec accounting sh

task-tracker-sh:
	docker-compose exec task-tracker sh

sender-sh:
	docker-compose exec sender sh
