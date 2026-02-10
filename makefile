migrate:
	php artisan db:wipe && php artisan migrate:fresh
migrate-seed:
	cd docker && docker-compose exec app bash && php artisan db:wipe && php artisan migrate:fresh --seed
down:
	cd docker && docker-compose down
start:
	cd docker && docker-compose up -d
to-workspace:
	cd docker && docker-compose exec app bash
composer-install:
	cd docker && docker-compose run --user fakel app composer install
docker-migrate-and-seed:
	cd docker && docker-compose run --user fakel app php artisan migrate:fresh --seed
rebuild:
	cd docker && docker-compose up --build -d
refresh:
	php artisan db:wipe && php artisan migrate && php artisan db:seed
