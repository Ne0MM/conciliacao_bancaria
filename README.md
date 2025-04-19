## Instrucoes para rodar projeto

# Versao do framework
1 - PHP 8.4.1
2 - Laravel 12.9.2
3 - Composer 2.8.3

## Passos para criar e conectar Data_base
# Crie um BD mySql
1 - nome do BD : extrato_data_base
2 - usuario do BD : root
3 - senha do BD : root
4 - port : 3306

obs : Voce pode mudar essas credenciais no .env

# Rode no seu terminal
1 - composer install
2 - php artisan migrate
3 - php artisan serve

# Para resetar o banco de dados rode
- php artisan migrate:fresh