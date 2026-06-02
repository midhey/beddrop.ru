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
5. Workflow `Mobile Android APK` автоматически запускается на опубликованный release, собирает APK и прикладывает файлы к этому же release.

Required GitHub Repository Secrets:

```text
RELEASE_PLEASE_TOKEN
EXPO_TOKEN
EXPO_PROJECT_ID
EXPO_OWNER
```

Что указывать:

- `RELEASE_PLEASE_TOKEN` - GitHub token для Release Please. Нужен не `GITHUB_TOKEN`, а отдельный fine-grained PAT или GitHub App token, чтобы созданный release запускал следующий workflow `Mobile Android APK`. Для fine-grained PAT дайте доступ только к репозиторию `midhey/beddrop.ru`: `Contents: Read and write`, `Pull requests: Read and write`, `Metadata: Read-only`, `Workflows: Read and write`.
- `EXPO_TOKEN` - Expo access token. Создается в Expo dashboard: Account settings -> Access Tokens. Нужен token от аккаунта/организации, у которого есть доступ к проекту `beddrop-courier`.
- `EXPO_PROJECT_ID` - UUID Expo/EAS проекта. Его можно взять в Expo dashboard в настройках проекта или из `mobile/courier/.expo/config.json` после `npx eas-cli@latest init`.
- `EXPO_OWNER` - Expo account или organization slug, владелец проекта. Например `midhey`, если проект лежит в личном Expo аккаунте `midhey`.

Ручная сборка APK без ожидания Release Please тоже доступна:

1. `Actions`
2. `Mobile Android APK`
3. `Run workflow`
4. Указать `version`, например `1.0.1`

Запуск через GitHub CLI:

```bash
gh workflow run mobile-android-apk.yml --repo midhey/beddrop.ru -f version=1.0.1
```

`Mobile Android APK` делает:

- `npm ci`
- `npm run typecheck`
- `eas build --platform android --profile apk --wait`
- скачивает APK
- сохраняет APK как workflow artifact
- публикует APK в GHCR как OCI artifact: `ghcr.io/midhey/beddrop-courier-apk:<version>`
- прикладывает APK и sha256 к GitHub Release `beddrop-courier-v<version>`

## Флоу

- auth использует `client_type: "mobile"`;
- `access_token` и `refresh_token` хранятся в SecureStore;
- смена, геолокация, заказы и заработок работают через `/api/v1/courier/*`;
- кнопка `Вывести деньги` пока показывает заглушку `Пока не работает`.
