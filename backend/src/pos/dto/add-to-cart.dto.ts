import { IsInt, IsPositive, Min } from 'class-validator';

export class AddToCartDto {
  @IsInt()
  productId: number;

  @IsPositive()
  @Min(1)
  quantity: number;
}
