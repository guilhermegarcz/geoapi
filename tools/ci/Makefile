setup: install update_db
update_src:
	git pull
	docker-compose exec php php bin/console cache:clear
	docker-compose exec php php bin/console cache:warmup
update_db:
	docker-compose exec php php bin/console app:update-ip2location
install:
	chmod -R +x scripts
	./scripts/install.sh