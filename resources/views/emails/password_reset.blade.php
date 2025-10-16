
<!DOCTYPE html>
<html>
  <body>
    <p>Hello,</p>
    <p>You requested a password reset. Click the link below to reset your password.
       This link will expire in {{ $expiresInMinutes }} minutes.</p>

    <p><a href="{{ $resetUrl }}">{{ $resetUrl }}</a></p>

    <p>If you did not request this, you can safely ignore this email.</p>

    <p>Thanks,<br>Your App Team</p>
  </body>
</html>

