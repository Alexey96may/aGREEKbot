# aGREEKbot

A Telegram bot for learning Greek vocabulary through a quiz game. Users translate words between Greek and Russian, earn points, compete in chat leaderboards, and can request hints, multiple-choice options, or dictionary definitions. The bot works both in private chats and group chats (as an administrator).

Built with **pure PHP** (no framework), using **Composer** only for PSR-4 autoloading and PHPUnit for tests. It runs as a **Telegram webhook** — Telegram sends updates to a public HTTPS endpoint, and the bot responds via the Telegram Bot API.

---

## Features

- **Quiz game** — the bot asks a word in Greek (or Russian), the user replies with the translation.
- **Two game modes** — `toRus` (Greek → Russian) and `toGreek` (Russian → Greek), switchable with `/game_change`.
- **Commands**
    - `/start_game` — start or restart the game with a new question.
    - `/game_hint` — get an etymology hint for the current word.
    - `/game_options` — get 4 multiple-choice answers.
    - `/game_dictionary` (or the word «словарь») — get a dictionary definition.
    - `/game_info` — full list of commands and features.
- **Scoring & leaderboards** — «Мой счёт», «Мой рейтинг», «Рейтинг чата» (top 10 players per chat).
- **User suggestions** — messages starting with «Пожелание» are forwarded to the admin.
- **User-submitted questions** — messages starting with `#` are forwarded to the admin for review.
- **Mute prefix** — messages starting with `*` are ignored by the bot.
- **Per-chat state** — settings, scores, and question queues are stored per chat in `temp/`.

---

## Requirements

- **PHP 8.0+** (tested on PHP 8.5) with extensions: `fileinfo`, `intl`, `json`, `mbstring`, `curl`.
- **Composer** (for autoloading).
- **Web server** with HTTPS (Nginx + PHP-FPM recommended). Telegram requires HTTPS for webhooks — a self-signed certificate is acceptable if uploaded to Telegram via `setWebhook`.
- A **Telegram bot token** from [@BotFather](https://t.me/BotFather).
- Your **Telegram user ID** (get it from [@userinfobot](https://t.me/userinfobot)) — used as the admin ID for forwarded suggestions and questions.

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/Alexey96may/aGREEKbot.git
cd aGREEKbot
```
