<?php

// namespace App\Mail;

// use Illuminate\Bus\Queueable;
// use Illuminate\Mail\Mailable;
// use Illuminate\Queue\SerializesModels;

// class PasswordResetMail extends Mailable
// {
//     use Queueable, SerializesModels;

//     public $resetUrl;
//     public $expiresInMinutes;

//     public function __construct(string $resetUrl, int $expiresInMinutes = 60)
//     {
//         $this->resetUrl = $resetUrl;
//         $this->expiresInMinutes = $expiresInMinutes;
//     }

//     public function build()
//     {
//         return $this->subject('Your password reset link')
//                     ->view('emails.password_reset')
//                     ->with([
//                         'resetUrl' => $this->resetUrl,
//                         'expiresInMinutes' => $this->expiresInMinutes,
//                     ]);
//     }
// }
 namespace App\Mail; use Illuminate\Bus\Queueable; use Illuminate\Mail\Mailable; use Illuminate\Queue\SerializesModels; class PasswordResetMail extends Mailable { use Queueable, SerializesModels; public $resetUrl; public $expiresInMinutes; public function __construct(string $resetUrl, int $expiresInMinutes = 60) { $this->resetUrl = $resetUrl; $this->expiresInMinutes = $expiresInMinutes; } public function build() { return $this->subject('Your password reset link') ->view('emails.password_reset') ->with([ 'resetUrl' => $this->resetUrl, 'expiresInMinutes' => $this->expiresInMinutes, ]); } }