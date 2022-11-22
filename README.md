#### Install & Start:
docker-compose up -d; <br/>

docker-compose exec php /bin/bash -c "php bin/console doctrine:database:create";<br/>
docker-compose exec php /bin/bash -c "php bin/console doctrine:schema:create";<br/>
docker-compose exec php /bin/bash -c "php bin/console doctrine:fixtures:load"; <br/>

docker-compose exec php /bin/bash -c "php bin/console --env=test doctrine:database:create";<br/>
docker-compose exec php /bin/bash -c "php bin/console --env=test doctrine:schema:create";<br/>
docker-compose exec php /bin/bash -c "php bin/console --env=test doctrine:fixtures:load";<br/>
#### Drop:
docker-compose exec php /bin/bash -c "php bin/console doctrine:database:drop --force";<br/>
docker-compose exec php /bin/bash -c "php bin/console --env=test doctrine:database:drop --force";<br/>
#### Recreate:
docker-compose up -d --build --force-recreate
#### Enter to docker:
docker-compose exec php /bin/bash
#### remove all:
~~docker-compose down; docker rm -f $(docker ps -a -q); docker volume rm $(docker volume ls -q);~~

### Tests
docker-compose exec php /bin/bash -c "composer test";<br/>

### CS FIX
docker-compose exec php /bin/bash -c "composer cs-fix";<br/>
