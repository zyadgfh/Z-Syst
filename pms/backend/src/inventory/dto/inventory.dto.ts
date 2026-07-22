import { IsString, IsNumber, IsOptional, IsUUID, IsArray } from 'class-validator';
import { ApiProperty } from '@nestjs/swagger';

export class CreatePurchaseOrderDto {
  @ApiProperty({ description: 'Branch ID' })
  @IsUUID()
  branchId: string;

  @ApiProperty({ description: 'Supplier ID' })
  @IsUUID()
  supplierId: string;

  @ApiProperty({ description: 'Items to order', type: [Object] })
  @IsArray()
  items: Array<{
    productId: string;
    quantity: number;
    unitPrice: number;
  }>;
}

export class ExpiryManagementDto {
  @ApiProperty({ description: 'Product ID' })
  @IsUUID()
  productId: string;

  @ApiProperty({ description: 'Branch ID' })
  @IsUUID()
  branchId: string;

  @ApiProperty({ required: false, description: 'Batch Number' })
  @IsOptional()
  @IsString()
  batchNumber?: string;
}