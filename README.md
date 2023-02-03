# Uputstvo za instalaciju i pokretanje

Pretpostvlja se da su PHP8.x, Composer i Docker instalirani. Prvo treba klonirati projekat

```
git clone https://github.com/krdr-mft/abstract-exercise.git .
```

Doker je podešen i pokreće se sa:
```
docker-compose up
```
Gašenje dokera se može izvršiti sa Ctrl+C u CLI.

Aplikacija je dostupna na adresi http://127.0.0.1:81/. Pokretanjem http://127.0.0.1:81/index.php pokrenuće aplikaciju koja će pokrenuti servis, proslediti potrebne objekte i kontaktirati servis.

Testovi mogu da se pokrenu sa:
```
docker-compose run phpunit --verbose tests
```

## Napomena

Napravio sam koncepcijsku grešku. Naime, trebalo je kreirati odgovarajuće klase i inicijalizovali odgovarajući objekti, a u kojima bi se nalazila pravila za prava pristupa.

---
U slučaju da dođe do kolizije sa portovima, promeniti portove u docker-compose.yaml, i to za _web_ i _mysql_. Npr, za web servis:

```
    web:
        image: nginx:latest
        ports:
            - "81:80"
```
promenti u:
```
    web:
        image: nginx:latest
        ports:
            - "8081:80"
```
---
Nakon ovih, ali i drugih promena na docker-compose.yaml, poželjno je pokrenuti sledeće komande:
```
docker-compose down -v
sudo systemctl restart docker.socket docker.service;
```
zatim podignuti doker.
