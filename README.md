# Laravel Two-Factor Authentication (2FA) Service

## Overview
This package provides a robust and easy-to-use Two-Factor Authentication (2FA) system for Laravel applications. It supports:

- Generating secure 2FA codes.
- Sending 2FA codes via email.
- Validating user-provided 2FA codes.

## Features
- **Secure Code Generation**: Uses random strings for secure 2FA codes.
- **Email Delivery**: Sends codes directly to the user's registered email.
- **Validation Mechanism**: Ensures the provided code matches the stored code securely.

## Installation
1. Clone the repository or copy the necessary files into your Laravel project.
2. Run `composer install` to ensure all dependencies are installed.
3. Configure your `.env` file with mail settings for email delivery:

   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=your_mail_host
   MAIL_PORT=587
   MAIL_USERNAME=your_username
   MAIL_PASSWORD=your_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@example.com
   MAIL_FROM_NAME="Your App Name"
   ```

4. Create a Blade view for the 2FA email:

   ```bash
   resources/views/emails/two_factor_code.blade.php
   ```
   Content of the file:
   ```html
   <p>Your 2FA Code is: <strong>{{ $code }}</strong></p>
   ```

## Usage

### 1. Add the Service to Your Project
Include the `TwoFactorAuthService` class in your application. Place it in `app/Services/TwoFactorAuthService.php`.

### 2. Create a Controller
Use the `TwoFactorAuthController` for sending and validating codes. Add the controller to your `app/Http/Controllers` directory.

### 3. Define Routes
Add the following routes to `routes/web.php` or `routes/api.php`:

```php
use App\Http\Controllers\TwoFactorAuthController;

Route::post('/2fa/send-code', [TwoFactorAuthController::class, 'sendCode']);
Route::post('/2fa/validate-code', [TwoFactorAuthController::class, 'validateCode']);
```

### 4. Protect Sensitive Routes
Apply middleware to routes that require 2FA verification:

```php
Route::group(['middleware' => ['auth', '2fa']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

## Example Usage

### Sending a Code
Send a 2FA code to the currently authenticated user:

```http
POST /2fa/send-code
```

### Validating a Code
Validate the code provided by the user:

```http
POST /2fa/validate-code
Content-Type: application/json

{
    "code": "123456"
}
```

Response:
- **200 OK**: Code validated successfully.
- **400 Bad Request**: Invalid or expired code.

## Extending
You can extend this package to support other delivery mechanisms (e.g., SMS, push notifications) by modifying the `sendCode` method in the `TwoFactorAuthService` class.

## Contributing
Feel free to submit pull requests or open issues for any improvements or bugs.

## License
This project is open-sourced software licensed under the [MIT license](LICENSE).

