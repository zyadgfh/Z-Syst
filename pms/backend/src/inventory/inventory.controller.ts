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
} from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse } from '@nestjs/swagger';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { InventoryService } from './inventory.service';
import { CreatePurchaseOrderDto, ExpiryManagementDto } from './dto/inventory.dto';

@ApiTags('Inventory Management')
@Controller('api/inventory')
@UseGuards(JwtAuthGuard)
export class InventoryController {
  constructor(private readonly inventoryService: InventoryService) {}

  @Get('overview')
  @ApiOperation({ summary: 'Stock Overview - All inventory items' })
  @ApiResponse({ status: 200, description: 'List of all inventory items with expiry dates' })
  async getStockOverview(@Query('branchId') branchId: string) {
    return this.inventoryService.getStockOverview(branchId);
  }

  @Get('shortages')
  @ApiOperation({ summary: 'Smart Shortages & AI Procurement Suggestions' })
  @ApiResponse({ status: 200, description: 'AI-powered procurement recommendations' })
  async getSmartShortages(@Query('branchId') branchId: string) {
    return this.inventoryService.getSmartShortages(branchId);
  }

  @Get('expiry')
  @ApiOperation({ summary: 'Expiry Management - Items expiring soon' })
  @ApiResponse({ status: 200, description: 'Items expiring within 30, 60, 90 days' })
  async getExpiryManagement(
    @Query('days') days: number = 90,
    @Query('branchId') branchId: string,
  ) {
    return this.inventoryService.getExpiryManagement(days, branchId);
  }

  @Post('purchase-order')
  @ApiOperation({ summary: 'Create Auto Purchase Order' })
  @ApiResponse({ status: 201, description: 'Purchase order created' })
  async createPurchaseOrder(@Body() createPO: CreatePurchaseOrderDto) {
    return this.inventoryService.createAutoPurchaseOrder(createPO);
  }

  @Post('transfer')
  @ApiOperation({ summary: 'Smart Stock Transfer Between Branches' })
  @ApiResponse({ status: 200, description: 'Stock transfer initiated' })
  async smartTransfer(@Body() body: { fromBranchId: string; toBranchId: string; items: any[] }) {
    return this.inventoryService.smartTransfer(body.fromBranchId, body.toBranchId, body.items);
  }

  @Post('clearance')
  @ApiOperation({ summary: 'Apply Clearance Discount (20%)' })
  async applyClearance(@Body() expiryDto: ExpiryManagementDto) {
    return this.inventoryService.applyClearanceDiscount(expiryDto);
  }

  @Post('return-supplier')
  @ApiOperation({ summary: 'Return to Supplier (90 days before expiry)' })
  async returnToSupplier(@Body() expiryDto: ExpiryManagementDto) {
    return this.inventoryService.returnToSupplier(expiryDto);
  }
}