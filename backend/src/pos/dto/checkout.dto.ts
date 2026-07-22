import { IsInt, IsPositive, Min } from 'class-validator';

export class CheckoutDto {
  @IsInt()
  branchId: number;

  // Additional fields like paymentMethod could be added later
}
