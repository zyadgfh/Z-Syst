import { IsEmail, IsString, MinLength, IsOptional, IsIn } from 'class-validator';

type Role = 'ADMIN' | 'PHARMACIST' | 'MANAGER' | 'STAFF';

export class RegisterDto {
  @IsEmail()
  email: string;

  @IsString()
  @MinLength(6)
  password: string;

  @IsOptional()
  @IsIn(['ADMIN', 'PHARMACIST', 'MANAGER', 'STAFF'])
  role?: Role;
}
