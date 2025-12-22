<?php
enum UserOpTypes: string {
  case verifyEmail = 'verifyEmail';
  case changeEmail = 'changeEmail';
  case resetPass = 'resetPass';

  public function tokenField(): string {
    return match ($this) {
      self::verifyEmail => 'verifyEmailToken',
      self::changeEmail => 'changeEmailToken',
      self::resetPass => 'resetPassToken',
    };
  }

  public function timeField(): string {
    return match ($this) {
      self::verifyEmail => 'vetCreatedAt',
      self::changeEmail => 'cetCreatedAt',
      self::resetPass => 'rptCreatedAt',
    };
  }

  public function urlParam(): string {
    return match ($this) {
      self::verifyEmail => 'vToken',
      self::changeEmail => 'eToken',
      self::resetPass => 'rToken',
    };
  }

  public function emailTemplate(): string {
    return match ($this) {
      self::verifyEmail => 'verifyEmail',
      self::changeEmail => 'changeEmail',
      self::resetPass => 'resetPass',
    };
  }
  public function tokenLifeTime(): int {
    return match ($this) {
      self::verifyEmail => 0,
      self::changeEmail => 86400,
      self::resetPass => 21600,
    };
  }//Время жизни токенов в секундах. 0 - бессрочно

}
