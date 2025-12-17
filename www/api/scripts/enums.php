<?php
enum UserOpTypes:string {
  case changeEmail = 'changeEmailToken';
  case verifyEmail = 'verifyEmailToken';
  case resetPass = 'resetPassToken';
}