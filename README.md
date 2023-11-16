# Repocket Stats

## What is this?
This is a pretty simple dockerized php page.
It shows you the stats of your balance and device amount and displays it on a nice page with a few graphs.

## How does it work?
It uses the repocket api to get the data and stores it in a database, once a minute.
It then uses the database to display the data on the page.
And the docker container uses the [alpine-php-webserver](https://github.com/erseco/alpine-php-webserver) docker image to host the page easily.

## Example
A screenshot of a sample looking dashbaord.
![image](https://github.com/hibenji/repocket_stats/assets/65447501/37014f00-7b9e-4c42-9e8a-133838024ec6)

## How to use?
Using Docker:
```
docker run -it -p 8070:8080 --rm \
--name=repocket_stats \
-e NAME=<Your nickname> \
-e EMAIL=<repocket-email> \
-e PASSWORD=<repocket-password> \
-v /repocket_stats:/var/www/html/config:777 \
repocket_stats
```

Give access to the config folder (where the database is stored):
```
sudo chown nobody:nogroup /repocket_stats
```

`8070`: The port it will be hosted at. You can change this to whatever you want.

`<Your nickname>`: The name you want to be displayed on the page.

`<repocket-email>`: The email you use to login to repocket.

`<repocket-password>`: The password you use to login to repocket.

`/repocket_stats`: The path where the config will be stored. You can change this to whatever you want.

## How to access the page?
You can access the page by going to `http://<your-ip>:8070` (or whatever port you chose).
