import { Injectable, BadRequestException } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { AiService } from '../ai/ai.service';
import { InventoryService } from '../inventory/inventory.service';
import { AddToCartDto } from './dto/add-to-cart.dto';
import { CheckoutDto } from './dto/checkout.dto';
import { UploadPrescriptionDto } from './dto/upload-prescription.dto';

@Injectable()
export class PosService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly aiService: AiService,
    private readonly inventoryService: InventoryService,
  ) {}

  async addToCart(userId: number, dto: AddToCartDto) {
    const product = await this.prisma.product.findUnique({ where: { id: dto.productId } });
    if (!product) {
      throw new BadRequestException('Product not found');
    }
    if (Number(product.price) <= 0) {
      throw new BadRequestException('Invalid product price');
    }
    return this.prisma.cartItem.create({
      data: {
        userId,
        productId: dto.productId,
        quantity: dto.quantity,
        price: product.price,
      },
    });
  }

  async checkout(userId: number, dto: CheckoutDto) {
    // Get all cart items for the user
    const items = await this.prisma.cartItem.findMany({
      where: { userId },
      include: { product: true },
    });
    if (items.length === 0) {
      throw new BadRequestException('Cart is empty');
    }

    // Calculate total
    const subtotal = items.reduce((sum, i) => sum + Number(i.price) * i.quantity, 0);
    const tax = subtotal * 0.08;
    const total = subtotal + tax;

    // Validate stock availability before checkout
    for (const item of items) {
      const stock = await this.prisma.stock.findFirst({
        where: { productId: item.productId, branchId: dto.branchId },
      });
      if (!stock || stock.quantity < item.quantity) {
        throw new BadRequestException(
          `Insufficient stock for ${item.product?.name || 'product'}`,
        );
      }
    }

    // Create sale with items
    const sale = await this.prisma.sale.create({
      data: {
        userId,
        branchId: dto.branchId,
        totalAmount: total,
        status: 'COMPLETED',
        items: {
          create: items.map((i) => ({
            productId: i.productId,
            quantity: i.quantity,
            price: i.price,
          })),
        },
      },
    });

    // Deduct stock for each item in a transaction
    await this.prisma.$transaction(async (prisma) => {
      for (const item of items) {
        await this.inventoryService.decreaseQuantity(item.productId, dto.branchId, item.quantity);
      }
      // Clear cart
      await prisma.cartItem.deleteMany({ where: { userId } });
    });

    return {
      ...sale,
      subtotal,
      tax,
      total,
    };
  }

  async getAllSales() {
    return this.prisma.sale.findMany({
      orderBy: { createdAt: 'desc' },
      include: {
        items: {
          include: { product: true },
        },
      },
    });
  }

  async uploadPrescription(userId: number, dto: UploadPrescriptionDto) {
    const { filePath, patientId } = dto;
    // Create prescription record (unprocessed)
    const prescription = await this.prisma.prescription.create({
      data: { patientId, imageUrl: filePath, processed: false },
    });
    // Perform OCR using AI service
    const ocrResult = await this.aiService.performOcr(filePath);
    // Update prescription as processed (optional store OCR result elsewhere)
    await this.prisma.prescription.update({
      where: { id: prescription.id },
      data: { processed: true },
    });
    return { prescriptionId: prescription.id, ocrResult };
  }
}