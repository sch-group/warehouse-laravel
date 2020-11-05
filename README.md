Laravel package для Моего склада

при развертывании пакета на каком-то новом проекте 
```bash
php artisan vendor:publish --provider="SchGroup\MyWarehouse\MyWarehouseServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="SchGroup\MyWarehouse\MyWarehouseServiceProvider" --tag="config"
```

Далее нужно запустить 
```bash
php artisan migrate
```
Создастся таблица my_warehouse_entities

Команда ниже заливает в мой склад товары, бренды, упаковки, статусы, бонусы в мой склад
```bash
php artisan my-warehouse:synchronize --entity=brand
php artisan my-warehouse:synchronize --entity=product
php artisan my-warehouse:synchronize --entity=variant
php artisan my-warehouse:synchronize --entity=bonus
php artisan my-warehouse:synchronize --entity=order_status
```
Перед запуском необходимо выполнить для всех этих сущностей.
Команда свяжет сущности в полиморфной таблице my_warehouse_entities

Команда ниже обновляет среднюю закупочную цену в Модификациях (упаковках) в моем складе, можно запускать раз в сутки
```bash
php artisan my-warehouse:synchronize_prices --entity=variant
```

ПЕРВЫЙ ЗАПУСК:

ВКЛЮЧИТЬ ЛОГИРОВАНИЕ И ПРОД РЕЖИМ В ENV (если мы на проде)
```bash
MY_WAREHOUSE_LOG=on
MY_WAREHOUSE=prod
```

При первоначальной запуске моего склада необходимо залить текущие остатки по всем упаковкам и бонусам.
Команды ниже зальют оприходования (Enters) в моем складе (вкладка оприходования).
Остатки по всем упаковкам бонусам можно посмотреть во вкладке Товары->Остатки
```bash
php artisan my-warehouse:add_first_stock_enters --entity=variant
php artisan my-warehouse:add_first_stock_enters --entity=brand
```

После того как все остатки залиты, необходимо сразу же залить заказы, которые находятся в статусах, резервирующих заказ 

```bash
php artisan my-warehouse:add_reserved_orders
```
Заказы должны появится во вкладке Заказы покупателей.
Если открыть вкладку Товары->Остатки и выбрать какую-то упаковку, то можно увидеть, список заказов зарезервировавших ее


СИНХРОНИЗАЦИЯ:

Если заказ переходит в статус Доставляется, то мы должны убрать упаковку из резерва и уменьшить остаток.
это происходит в джобе
```bash
UpdateOrderInMyWarehouseJob
```
Джоба создает документ Отгрузки и убирает заказ из резерва, при отмене заказа отгрузка удаляется.
Синхронно меняется статус и состав упаковок и бонусов

Новые заказы создаются синхронно в джобе
```bash
CreateOrderInMyWarehouseJob
```

ИНВЕНТАРИЗАЦИЯ/ПРИХОДЫ/СПИСАНИЯ:

```bash
PerformChangeInMyWarehouseJob
```
При добавлении прихода в нашей админке (Incoming) в моем склале создается документ приемки Supply

При инвентаризации в нашей админке: 

если добавилась находка (т.е реальное количество упаковки на складе оказалось больше), 
то создается новое оприходование Enter в моем складе

если добавилось списание списание (т.е реальное количество упаковки на складе оказалось меньше), 
то создается новое списание Loss в моем складе

Чтобы сверять состояние, можно заходить в инвентаризацию в админке и сверять с вкладкой Остатки в Моем складе 

ЕСЛИ ЧТО-ТО ПОШЛО НЕ ТАК:

Смотрим логи, узнаем почему, фиксим

Удаляем в моем складе все документы заказы, оприходования, списания, приемки, и повторяем шаги начиная с ПЕРВЫЙ ЗАПУСК


