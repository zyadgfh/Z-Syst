import { Controller, Post, Get, Body, UseGuards, Request } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiBearerAuth } from '@nestjs/swagger';
import { PosService } from './pos.service';
import { AddToCartDto } from './dto/add-to-cart.dto';
import { CheckoutDto } from './dto/checkout.dto';
import { UploadPrescriptionDto } from './dto/upload-prescription.dto';
import { JwtAuthGuard } from '../auth/jwt-auth.guard';

@ApiTags('POS')
@Controller('pos')
export class PosController {
  constructor(private readonly posService: PosService) {}

  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @Post('cart')
  @ApiOperation({ summary: 'Add a product to the user cart' })
  @ApiResponse({ status: 201, description: 'Cart item added' })
  async addToCart(@Request() req, @Body() dto: AddToCartDto) {
    const userId = req.user.userId;
    return this.posService.addToCart(userId, dto);
  }

  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @Post('checkout')
  @ApiOperation({ summary: 'Checkout the user cart' })
  @ApiResponse({ status: 201, description: 'Checkout completed, sale created' })
  async checkout(@Request() req, @Body() dto: CheckoutDto) {
    const userId = req.user.userId;
    return this.posService.checkout(userId, dto);
  }

  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @Get('sales')
  @ApiOperation({ summary: 'Get all sales records' })
  @ApiResponse({ status: 200, description: 'Sales fetched successfully' })
  async getSales() {
    return this.posService.getAllSales();
  }

  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @Post('prescription')
  @ApiOperation({ summary: 'Upload prescription image for OCR processing' })
  @ApiResponse({ status: 201, description: 'Prescription saved with OCR result' })
  async uploadPrescription(@Request() req, @Body() dto: UploadPrescriptionDto) {
    const userId = req.user.userId;
    return this.posService.uploadPrescription(userId, dto);
  }
}