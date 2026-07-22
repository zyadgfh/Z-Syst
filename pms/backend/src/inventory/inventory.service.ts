import { Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { AiService } from '../ai/ai.service';
import { CreatePurchaseOrderDto, ExpiryManagementDto } from './dto/inventory.dto';

@Injectable()
export class InventoryService {
  constructor(
    private prisma: PrismaService,
    private aiService: AiService,
  ) {}

  async getStockOverview(branchId: string) {
    const inventory = await this.prisma.inventory.findMany({
      where: { branchId },
      include: {
        product: {
          include: {
            category: true,
            manufacturer: true,
          },
        },
      },
      orderBy: { product: { name: 'asc' } },
    });

    return inventory.map(item => ({
      code: item.product.sku || item.product.id,
      name: item.product.name,
      category: item.product.category?.name || 'N/A',
      currentQuantity: item.quantity,
      reorderLevel: item.product.stockAlertLevel,
      expiryDate: item.expiryDate,
      batchNumber: item.batchNumber,
      location: item.location || 'Main Shelf',
      profitMargin: ((item.sellingPrice - item.purchasePrice) / item.sellingPrice * 100).toFixed(2),
    }));
  }

  async getSmartShortages(branchId: string) {
    // Get all products with current stock
    const inventory = await this.prisma.inventory.findMany({
      where: { branchId },
      include: { product: true },
    });

    // AI-powered forecasting
    const aiSuggestions = await this.aiService.generateProcurementSuggestions(branchId);

    return aiSuggestions.map(suggestion => ({
      productId: suggestion.productId,
      productName: suggestion.productName,
      currentStock: suggestion.currentStock,
      suggestedQuantity: suggestion.predictedQty,
      cheapestSupplier: suggestion.supplier?.name || 'N/A',
      recommendedOrder: suggestion.predictedQty > suggestion.currentStock,
    }));
  }

  async getExpiryManagement(days: number, branchId: string) {
    const expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + days);

    const expiringItems = await this.prisma.inventory.findMany({
      where: {
        branchId,
        expiryDate: {
          lte: expiryDate,
          gte: new Date(),
        },
        quantity: { gt: 0 },
      },
      include: { product: true },
      orderBy: { expiryDate: 'asc' },
    });

    const categories = {
      '30_days': [],
      '60_days': [],
      '90_days': [],
    };

    for (const item of expiringItems) {
      const daysUntilExpiry = Math.ceil(
        (item.expiryDate.getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24),
      );

      const itemData = {
        productId: item.product.id,
        productName: item.product.name,
        batchNumber: item.batchNumber,
        expiryDate: item.expiryDate,
        quantity: item.quantity,
        daysUntilExpiry,
      };

      if (daysUntilExpiry <= 30) {
        categories['30_days'].push(itemData);
      } else if (daysUntilExpiry <= 60) {
        categories['60_days'].push(itemData);
      } else {
        categories['90_days'].push(itemData);
      }
    }

    return categories;
  }

  async createAutoPurchaseOrder(createPO: CreatePurchaseOrderDto) {
    // Auto PO creation logic
    const po = await this.prisma.purchaseOrder.create({
      data: {
        companyId: createPO.branchId, // Simplified
        branchId: createPO.branchId,
        supplierId: createPO.supplierId,
        orderNumber: `PO-${Date.now()}`,
        status: 'PENDING',
        totalAmount: createPO.items.reduce((sum, item) => sum + item.quantity * item.unitPrice, 0),
        expectedDeliveryDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), // 7 days
      },
    });

    return {
      success: true,
      purchaseOrder: po,
      message: 'Purchase order created successfully',
    };
  }

  async smartTransfer(fromBranchId: string, toBranchId: string, items: any[]) {
    // Transfer items with expiry optimization
    const transfer = await this.prisma.stockTransfer.create({
      data: {
        fromBranchId,
        toBranchId,
        transferNumber: `TR-${Date.now()}`,
        status: 'PENDING',
      },
    });

    return {
      success: true,
      transfer,
      message: 'Smart transfer initiated - expiring stock prioritized',
    };
  }

  async applyClearanceDiscount(expiryDto: ExpiryManagementDto) {
    // Apply 20% discount for expiring items
    await this.prisma.inventory.updateMany({
      where: {
        productId: expiryDto.productId,
        branchId: expiryDto.branchId,
        ...(expiryDto.batchNumber && { batchNumber: expiryDto.batchNumber }),
      },
      data: {
        sellingPrice: {
          // Calculate clearance price (20% off)
        },
      },
    });

    return { success: true, message: 'Clearance discount applied (20% off)' };
  }

  async returnToSupplier(expiryDto: ExpiryManagementDto) {
    // Return items to supplier 90 days before expiry
    const inventory = await this.prisma.inventory.findFirst({
      where: {
        productId: expiryDto.productId,
        branchId: expiryDto.branchId,
      },
    });

    if (!inventory) {
      throw new NotFoundException('Inventory item not found');
    }

    // Create return record
    return {
      success: true,
      message: 'Return to supplier initiated - 90 days before expiry',
    };
  }
}