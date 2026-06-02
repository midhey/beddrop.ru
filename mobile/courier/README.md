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
EXPO_PUBLIC_API_BASE=https://api.beddrop.ru/api/v1 npm run start
```

## Android APK releases

Версионирование мобильного приложения идет через Release Please компонент `beddrop-courier`. Web/API часть версионируется отдельно компонентом `beddrop-web`.

Основной flow:

1. Коммиты в `master` пишутся в Conventional Commits формате:
   - `fix(mobile): ...` -> patch release
   - `feat(mobile): ...` -> minor release
   - `feat(mobile)!: ...` или `BREAKING CHANGE:` -> major release
2. Workflow `Release Please` создает или обновляет release PR.
3. После merge release PR обновляются:
   - `mobile/courier/package.json`
   - `mobile/courier/package-lock.json`
   - `.release-please-manifest.json`
   - changelog, если Release Please его создаст
4. Release Please создает tag вида `beddrop-courier-v<version>` и GitHub Release.
5. Workflow `Mobile Android APK Start` автоматически запускает EAS build и быстро завершается, не ожидая очередь Expo.
6. Когда EAS build станет `Finished`, вручную запускается `Mobile Android APK Publish`, который скачивает APK и прикладывает файлы к GitHub Release.

Required GitHub Repository Secrets:

```text
RELEASE_PLEASE_TOKEN
EXPO_TOKEN
```

Required GitHub Repository Variables:

```text
EXPO_PROJECT_ID
EXPO_OWNER
```

`EXPO_PROJECT_ID` и `EXPO_OWNER` не должны быть secrets. Expo CLI печатает ссылку на build вида `https://expo.dev/accounts/<owner>/projects/...`; если owner хранится в secrets, GitHub замаскирует его как `***`, и ссылка из Actions логов станет невалидной.

Что указывать в secrets:

- `RELEASE_PLEASE_TOKEN` - GitHub token для Release Please. Нужен не `GITHUB_TOKEN`, а отдельный fine-grained PAT или GitHub App token, чтобы созданный release запускал следующий workflow `Mobile Android APK`. Для fine-grained PAT дайте доступ только к репозиторию `midhey/beddrop.ru`: `Contents: Read and write`, `Pull requests: Read and write`, `Metadata: Read-only`, `Workflows: Read and write`.
- `EXPO_TOKEN` - Expo access token. Создается в Expo dashboard: Account settings -> Access Tokens. Нужен token от аккаунта/организации, у которого есть доступ к проекту `beddrop-courier`.

Что указывать в variables:

- `EXPO_PROJECT_ID` - UUID Expo/EAS проекта. Его можно взять в Expo dashboard в настройках проекта или из `mobile/courier/.expo/config.json` после `npx eas-cli@latest init`.
- `EXPO_OWNER` - Expo account или organization slug, владелец проекта. Например `midhey`, если проект лежит в личном Expo аккаунте `midhey`.

Ручный старт APK build без ожидания Release Please тоже доступен:

1. `Actions`
2. `Mobile Android APK Start`
3. `Run workflow`
4. Указать `version`, например `1.0.1`

Запуск через GitHub CLI:

```bash
gh workflow run mobile-android-apk.yml --repo midhey/beddrop.ru -f version=1.0.1
```

`Mobile Android APK Start` делает:

- `npm ci`
- `npm run typecheck`
- `eas build --platform android --profile apk --no-wait`
- пишет EAS build ID и ссылку в job summary
- не ждет очередь Expo и не расходует GitHub Actions minutes на ожидание

Когда build в Expo dashboard перешел в `Finished`, запустите `Mobile Android APK Publish`:

1. `Actions`
2. `Mobile Android APK Publish`
3. `Run workflow`
4. Указать:
   - `eas_build_id` из job summary или Expo dashboard
   - `version`, например `1.0.1`
   - `release_tag`, если нужен нестандартный tag; обычно пусто

Через GitHub CLI:

```bash
gh workflow run mobile-android-apk-publish.yml --repo midhey/beddrop.ru -f eas_build_id=<eas-build-id> -f version=1.0.1
```

`Mobile Android APK Publish` делает:

- проверяет, что EAS build уже `FINISHED`
- скачивает APK
- сохраняет APK как workflow artifact
- публикует APK в GHCR как OCI artifact: `ghcr.io/midhey/beddrop-courier-apk:<version>`
- прикладывает APK и sha256 к GitHub Release `beddrop-courier-v<version>`

## Флоу

- auth использует `client_type: "mobile"`;
- `access_token` и `refresh_token` хранятся в SecureStore;
- смена, геолокация, заказы и заработок работают через `/api/v1/courier/*`;
- кнопка `Вывести деньги` пока показывает заглушку `Пока не работает`.
