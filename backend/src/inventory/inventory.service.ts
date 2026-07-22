import { Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { CreateStockDto } from './dto/create-stock.dto';
import { UpdateStockDto } from './dto/update-stock.dto';

@Injectable()
export class InventoryService {
  constructor(private readonly prisma: PrismaService) {}

  async createStock(dto: CreateStockDto) {
    const { productId, branchId, quantity, batchNumber, expiryDate } = dto;
    // Ensure product and branch exist
    const product = await this.prisma.product.findUnique({ where: { id: productId } });
    if (!product) {
      throw new NotFoundException('Product not found');
    }
    const branch = await this.prisma.branch.findUnique({ where: { id: branchId } });
    if (!branch) {
      throw new NotFoundException('Branch not found');
    }
    return this.prisma.stock.create({
      data: { productId, branchId, quantity, batchNumber, expiryDate },
    });
  }

  async updateStock(id: number, dto: UpdateStockDto) {
    const stock = await this.prisma.stock.findUnique({ where: { id } });
    if (!stock) {
      throw new NotFoundException('Stock entry not found');
    }
    return this.prisma.stock.update({ where: { id }, data: dto });
  }

  async getStocksByBranch(branchId: number) {
    return this.prisma.stock.findMany({ where: { branchId } });
  }

  // Decrease quantity when a sale occurs (used by POS checkout)
  async decreaseQuantity(productId: number, branchId: number, amount: number) {
    const stock = await this.prisma.stock.findFirst({
      where: { productId, branchId },
    });
    if (!stock || stock.quantity < amount) {
      throw new NotFoundException('Insufficient stock');
    }
    return this.prisma.stock.update({
      where: { id: stock.id },
      data: { quantity: stock.quantity - amount },
    });
  }
}
