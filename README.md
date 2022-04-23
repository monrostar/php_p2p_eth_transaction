## In order to start the server, you need to run several commands

```shell
composer install --prefer-dist
php -S localhost:8080 -t .
```

### In order to run the send script, you need to run this command.
```shell
php loop.php
# or with maxGasPrice arg
php loop.php --maxGasPrice=140 --maxBalanceAmount=0.2
```

> Now you can use ^_^
