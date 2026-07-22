import { Controller, Get, Param, ParseIntPipe, UseGuards } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse } from '@nestjs/swagger';
import { ProductsService } from './products.service';
import { JwtAuthGuard } from '../auth/jwt-auth.guard';

@ApiTags('Products')
@Controller('products')
@UseGuards(JwtAuthGuard)
export class ProductsController {
  constructor(private readonly productsService: ProductsService) {}

  @Get('branch/:branchId')
  @ApiOperation({ summary: 'List products for a branch with current stock' })
  @ApiResponse({ status: 200, description: 'Products fetched successfully' })
  async getProducts(@Param('branchId', ParseIntPipe) branchId: number) {
    return this.productsService.getProducts(branchId);
  }
}
