# FinFlow Backend API

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge\&logo=php\&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=for-the-badge\&logo=mysql\&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-Authentication-yellowgreen?style=for-the-badge\&logo=jsonwebtokens\&logoColor=white)
![RESTful](https://img.shields.io/badge/API-RESTful-blue?style=for-the-badge)

## 🚀 Overview

**FinFlow API** powers a modern personal finance application by providing secure authentication, bank integration, and financial data management. This API is designed with performance, scalability, and modularity in mind.

## 🧩 Features

* **Authentication & Authorization:**

  * Secure signup and login
  * Password reset and email verification
  * Token-based session management with JWT

* **Banking Integration:**

  * Connect to bank accounts using Mono
  * Fetch live balances and transaction history

* **Email Communication:**

  * Email verification & password reset via Mailtrap API

* **Secure API Endpoints:**

  * Middleware-protected routes
  * Rate-limiting ready (can be integrated)

## 🛠 Tech Stack

| Layer          | Technology         |
| -------------- | ------------------ |
| Backend        | PHP 8              |
| Architecture   | Modular MVC        |
| Authentication | JWT                |
| Integrations   | Mono API, Mailtrap |

## 📂 Folder Structure (v1)

```
├── api
│   └── auth                 # Auth-related endpoints
│   └── middleware           # Middleware like verifyJWT
├── assets
│   ├── controllers          # Business logic
│   ├── models               # Database access layer
│   ├── utils.php            # Reusable utilities
│   └── envDecoder.php       # Loads env configs
├── assets/env               # .env file lives here
├── assets/header.php        # CORS and response headers
└── index.php                # Entry point
```

## 📦 Installation & Usage

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/finflow.git
cd finflow/v1
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Server

Set your document root (Apache/Nginx) to the `v1` directory.

**For local dev:** Use XAMPP, Laragon, or PHP’s built-in server.

```bash
php -S localhost:8000
```

## 🌍 Live Demo

Live deployment coming soon...

## 🤝 Contributing

If you'd like to contribute, please fork the repository and submit a pull request. Bug reports and feature requests are also welcome!

## 📄 License

This project is released under the MIT License.


## 👨‍💻 About the Developer

Hi, I’m **Samuel Ayomide** – a passionate backend developer with experience building scalable APIs and backend systems using PHP, Laravel, and automation tools like n8n, Zapier, and Make. I enjoy solving real-world problems and creating developer-friendly backend solutions.

Connect with me on:

* [LinkedIn](https://linkedin.com/in/samuelayo0507)
* [Twitter](https://twitter.com/sam__ayo)



*This project is part of my backend portfolio to demonstrate practical API development, secure authentication flows, and integration with external services.*
