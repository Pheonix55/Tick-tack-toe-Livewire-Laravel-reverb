<img width="1468" height="718" alt="Screenshot 2026-02-21 104302" src="https://github.com/user-attachments/assets/f8f4229b-2dd2-493c-950c-8a8a3064defc" /># Real-Time Multiplayer Tic-Tac-Toe

A **multiplayer Tic-Tac-Toe game** built with **Laravel 12, Livewire 4, and Reverb broadcasting**, designed to handle **complex real-time event updates** and provide a seamless experience for two players.

---

## Table of Contents

* [Project Overview](#project-overview)
* [Features](#features)
* [Tech Stack](#tech-stack)
* [Installation](#installation)
* [Usage](#usage)
* [Screenshots](#screenshots)
* [Learning Goals](#learning-goals)
* [License](#license)

---

## Project Overview

This project demonstrates the use of **event-driven architecture in Laravel** to build a **fully interactive multiplayer game**. It focuses on real-time updates for game state synchronization, player turns, and winner detection, all without relying on heavy frontend frameworks.

---

## Features

* Real-time **lobby management** with multiple players
* **Turn validation** and **winner/draw detection** server-side
* Real-time **gameboard updates** for both players
* Responsive UI with **move animations**, **turn indicators**, and **winning highlights**
* Tracks which player can start the game (`can_start_game` flag in lobby)

---

## Tech Stack

* **Backend:** Laravel 12
* **Frontend:** Livewire 4, TailwindCSS / Bootstrap
* **Realtime Broadcasting:** Reverb
* **Database:** MySQL (Users, Games, Moves, Game Lobbies)

---

## Installation

1. Clone the repository:

```bash
git clone https://github.com/Pheonix55/tic-tac-toe-livewire.git
cd tic-tac-toe-livewire
```

2. Install PHP dependencies:

```bash
composer install
```

3. Install NPM dependencies:

```bash
npm install
npm run dev
```

4. Copy `.env.example` to `.env` and configure your database:

```bash
cp .env.example .env
php artisan key:generate
```

5. Run migrations:

```bash
php artisan migrate
```

6. Serve the project locally:

```bash
php artisan serve
```

---

## Usage

* Register or log in with two different users.
* Create a lobby and invite another player or join an existing one.
* Take turns making moves on the **3x3 grid**.
* The game ends when a player wins or the board results in a draw.
* Real-time updates are handled via Livewire 4 + Reverb, ensuring both players see the same game state instantly.

---

## Screenshots

1. **Game Lobby (Both Players)**
   <img width="908" height="471" alt="Screenshot 2026-02-21 104215" src="https://github.com/user-attachments/assets/8ee104d3-bca8-4dff-ba19-e9ec12da243c" />


2. **Game Screen (Both Players)**
   <img width="1468" height="718" alt="Screenshot 2026-02-21 104302" src="https://github.com/user-attachments/assets/a60039fe-b85d-43c7-93d8-407288d09757" />


3. **Game Finished Screen**
  <img width="1489" height="710" alt="Screenshot 2026-02-21 104205" src="https://github.com/user-attachments/assets/82199bd0-7fed-496d-acb7-a5cd8799df88" />


---

## Learning Goals

* Understand **real-time event handling** in Laravel 12 using Livewire 4 and Reverb
* Learn **server-side validation** for multiplayer game logic
* Gain experience in building **responsive, interactive UI** for real-time applications
* Explore **event-driven architecture** for small-scale games

---

## License

This project is **open-source** and available under the [MIT License](LICENSE).
