import {
  Controller,
  Get,
  Post,
  Patch,
  Param,
  Body,
  ParseIntPipe,
  UseGuards,
} from '@nestjs/common';
import { InventoryService } from './inventory.service';
import { CreateStockDto } from './dto/create-stock.dto';
import { UpdateStockDto } from './dto/update-stock.dto';
import { JwtAuthGuard } from '../auth/jwt-auth.guard';

@Controller('inventory')
@UseGuards(JwtAuthGuard)
export class InventoryController {
  constructor(private readonly inventoryService: InventoryService) {}

  /**
   * POST /inventory/stock
   * Create a new stock entry for a product at a branch.
   */
  @Post('stock')
  createStock(@Body() dto: CreateStockDto) {
    return this.inventoryService.createStock(dto);
  }

  /**
   * PATCH /inventory/stock/:id
   * Update quantity, batch number, or expiry date for a stock entry.
   */
  @Patch('stock/:id')
  updateStock(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: UpdateStockDto,
  ) {
    return this.inventoryService.updateStock(id, dto);
  }

  /**
   * GET /inventory/branch/:branchId
   * List all stock entries for a specific branch.
   */
  @Get('branch/:branchId')
  getStockByBranch(@Param('branchId', ParseIntPipe) branchId: number) {
    return this.inventoryService.getStocksByBranch(branchId);
  }
}
