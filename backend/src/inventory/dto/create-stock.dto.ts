import { IsInt, IsOptional, IsPositive, IsString, IsDateString } from 'class-validator';

export class CreateStockDto {
  @IsInt()
  productId: number;

  @IsInt()
  branchId: number;

  @IsPositive()
  quantity: number;

  @IsOptional()
  @IsString()
  batchNumber?: string;

  @IsOptional()
  @IsDateString()
  expiryDate?: string; // ISO date string
}
