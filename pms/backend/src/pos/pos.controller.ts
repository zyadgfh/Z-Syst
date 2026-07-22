import {
  Controller,
  Get,
  Post,
  Body,
  Patch,
  Param,
  Delete,
  Query,
  UseGuards,
  Req,
} from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse } from '@nestjs/swagger';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { PosService } from './pos.service';
import { CreateSaleDto, AddToCartDto, CheckoutDto, SmartSearchDto } from './dto/pos.dto';

@ApiTags('Point of Sale (POS)')
@Controller('api/pos')
@UseGuards(JwtAuthGuard)
export class PosController {
  constructor(private readonly posService: PosService) {}

  @Get('search')
  @ApiOperation({ summary: 'Smart Search for Products' })
  @ApiResponse({ status: 200, description: 'Search results with AI ranking' })
  async smartSearch(@Query() searchDto: SmartSearchDto) {
    return this.posService.smartSearch(searchDto);
  }

  @Get('cart')
  @ApiOperation({ summary: 'Get Current Cart Session' })
  @ApiResponse({ status: 200, description: 'Cart items with DDI alerts' })
  async getCart(@Req() req) {
    return this.posService.getCurrentCart(req.user.branchId);
  }

  @Post('cart/add')
  @ApiOperation({ summary: 'Add Item to Cart' })
  @ApiResponse({ status: 200, description: 'Item added with interaction alerts' })
  async addToCart(@Body() addToCartDto: AddToCartDto, @Req() req) {
    return this.posService.addItemToCart(addToCartDto, req.user.branchId, req.user.id);
  }

  @Delete('cart/:itemId')
  @ApiOperation({ summary: 'Remove Item from Cart' })
  async removeFromCart(@Param('itemId') itemId: string) {
    return this.posService.removeItemFromCart(itemId);
  }

  @Get('substitutes/:productId')
  @ApiOperation({ summary: 'Get Smart Substitutes for Product' })
  @ApiResponse({ status: 200, description: 'Substitute drugs ranked by profitability and price' })
  async getSmartSubstitutes(
    @Param('productId') productId: string,
    @Req() req
  ) {
    return this.posService.getSmartSubstitutes(productId, req.user.branchId);
  }

  @Post('checkout')
  @ApiOperation({ summary: 'Process Sale Checkout' })
  @ApiResponse({ status: 200, description: 'Sale completed with invoice' })
  async checkout(@Body() checkoutDto: CheckoutDto, @Req() req) {
    return this.posService.processCheckout(checkoutDto, req.user.branchId, req.user.id);
  }

  @Post('prescription/ocr')
  @ApiOperation({ summary: 'Upload Prescription for OCR Analysis' })
  @ApiResponse({ status: 200, description: 'Prescription parsed and items added to cart' })
  async processPrescription(@Body() body: any) {
    return this.posService.processPrescriptionOCR(body.imageUrl);
  }

  @Get('quick-sale')
  @ApiOperation({ summary: 'Fastest Sale Creation (< 3 seconds)' })
  @ApiResponse({ status: 200, description: 'Quick invoice generation' })
  async quickSale() {
    return this.posService.quickSaleLookup();
  }
}