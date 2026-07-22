import { IsString, IsNumber, IsOptional, IsEnum, IsArray, IsUUID } from 'class-validator';
import { ApiProperty } from '@nestjs/swagger';

export class SmartSearchDto {
  @ApiProperty({ description: 'Search query (name, barcode, active ingredient)' })
  @IsString()
  query: string;

  @ApiProperty({ required: false, description: 'Search type (name, barcode, ingredient, symptom)' })
  @IsOptional()
  @IsEnum(['name', 'barcode', 'ingredient', 'symptom'])
  searchType?: string = 'name';

  @ApiProperty({ required: false, description: 'Branch ID for stock check' })
  @IsOptional()
  @IsUUID()
  branchId?: string;
}

export class AddToCartDto {
  @ApiProperty({ description: 'Product ID' })
  @IsUUID()
  productId: string;

  @ApiProperty({ description: 'Quantity to add' })
  @IsNumber()
  quantity: number;

  @ApiProperty({ required: false, description: 'Batch number preference' })
  @IsOptional()
  @IsString()
  batchNumber?: string;

  @ApiProperty({ required: false, description: 'Customer ID for loyalty' })
  @IsOptional()
  @IsUUID()
  customerId?: string;
}

export class CheckoutDto {
  @ApiProperty({ required: false, description: 'Cart items (if not using session)' })
  @IsOptional()
  @IsArray({})
  items?: Array<{
    productId: string;
    quantity: number;
    discount?: number;
    batchNumber?: string;
  }>;

  @ApiProperty({ required: false, description: 'Customer ID' })
  @IsOptional()
  @IsUUID()
  customerId?: string;

  @ApiProperty({ description: 'Payment method' })
  @IsEnum(['CASH', 'CARD', 'WALLET', 'INSURANCE', 'CREDIT'])
  paymentMethod: string;

  @ApiProperty({ required: false, description: 'Paid amount' })
  @IsOptional()
  @IsNumber()
  paidAmount?: number;

  @ApiProperty({ required: false, description: 'Notes' })
  @IsOptional()
  @IsString()
  notes?: string;
}

export class PrescriptionUploadDto {
  @ApiProperty({ description: 'Base64 image or URL of prescription' })
  @IsString()
  image: string; // base64 or URL

  @ApiProperty({ required: false, description: 'Customer phone for WhatsApp integration' })
  @IsOptional()
  @IsString()
  phoneNumber?: string;
}