# Real-Time Multiplayer Tic-Tac-Toe

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
   ![Lobby Screen](https://www.awesomescreenshot.com/image/58925114?key=383f32ea211e03b75fb38895f5baf0f4)

2. **Game Screen (Both Players)**
   ![Game Screen](https://www.awesomescreenshot.com/image/58925115?key=a5a6b53b70b56615f5eb7caca0d32a82)

3. **Game Finished Screen**
   ![Winning Screen](https://www.awesomescreenshot.com/image/58925116?key=9769689a0d94eed8a55c12b2bfc6b848)

---

## Learning Goals

* Understand **real-time event handling** in Laravel 12 using Livewire 4 and Reverb
* Learn **server-side validation** for multiplayer game logic
* Gain experience in building **responsive, interactive UI** for real-time applications
* Explore **event-driven architecture** for small-scale games

---

## License

This project is **open-source** and available under the [MIT License](LICENSE).
