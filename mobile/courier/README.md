# BedDrop Courier Mobile

Expo managed Android-приложение для флоу курьера BedDrop.

## Запуск

```bash
npm install
npm run start
```

По умолчанию API: `http://10.0.2.2:8080/api/v1` для Android-эмулятора.
Для другого backend:

```bash
EXPO_PUBLIC_API_BASE=https://beddrop.ru/api/v1 npm run start
```

## Флоу

- auth использует `client_type: "mobile"`;
- `access_token` и `refresh_token` хранятся в SecureStore;
- смена, геолокация, заказы и заработок работают через `/api/v1/courier/*`;
- кнопка `Вывести деньги` пока показывает заглушку `Пока не работает`.
