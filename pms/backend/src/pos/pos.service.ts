import { Injectable, NotFoundException, BadRequestException } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { SmartSearchDto, AddToCartDto, CheckoutDto } from './dto/pos.dto';
import { AiService } from '../ai/ai.service';

@Injectable()
export class PosService {
  constructor(
    private prisma: PrismaService,
    private aiService: AiService,
  ) {}

  async smartSearch(searchDto: SmartSearchDto) {
    const { query, searchType = 'name', branchId } = searchDto;
    
    let whereCondition: any = {};
    
    switch (searchType) {
      case 'barcode':
        whereCondition = { barcode: query };
        break;
      case 'ingredient':
        whereCondition = { activeIngredient: { contains: query, mode: 'insensitive' } };
        break;
      case 'symptom':
        // AI-powered symptom to drug mapping
        const suggestedDrugs = await this.aiService.getDrugSuggestionsBySymptom(query);
        whereCondition = { id: { in: suggestedDrugs } };
        break;
      default:
        whereCondition = {
          OR: [
            { name: { contains: query, mode: 'insensitive' } },
            { slug: { contains: query, mode: 'insensitive' } },
            { activeIngredient: { contains: query, mode: 'insensitive' } },
          ],
        };
    }

    const products = await this.prisma.product.findMany({
      where: whereCondition,
      include: {
        inventory: branchId ? {
          where: { branchId },
        } : false,
      },
      take: 20,
    });

    // AI-powered ranking based on sales velocity, profit margin, and stock availability
    return this.aiService.rankSearchResults(products, branchId);
  }

  async getCurrentCart(branchId: string) {
    // Get cart from Redis session or create new
    const cartKey = `cart:${branchId}`;
    // Implementation would use Redis for fast cart operations
    return { items: [], total: 0, alerts: [] };
  }

  async addItemToCart(addToCartDto: AddToCartDto, branchId: string, userId: string) {
    const { productId, quantity, customerId } = addToCartDto;

    // Verify product exists and check stock
    const product = await this.prisma.product.findUnique({
      where: { id: productId },
      include: {
        inventory: {
          where: { branchId },
          orderBy: { expiryDate: 'asc' },
        },
      },
    });

    if (!product) {
      throw new NotFoundException('Product not found');
    }

    // Check for DDI (Drug-Drug Interactions)
    const ddiAlerts = await this.aiService.checkDrugInteractions(productId);

    // Get best batch (FIFO - first expiry first)
    const availableStock = product.inventory.reduce((sum, inv) => sum + inv.quantity, 0);
    if (availableStock < quantity) {
      throw new BadRequestException('Insufficient stock');
    }

    const cartItem = {
      productId,
      productName: product.name,
      quantity,
      unitPrice: product.sellingPrice,
      totalPrice: product.sellingPrice * quantity,
      batchNumber: product.inventory[0]?.batchNumber,
      expiryDate: product.inventory[0]?.expiryDate,
    };

    return {
      item: cartItem,
      alerts: ddiAlerts,
      availableStock,
    };
  }

  async removeItemFromCart(itemId: string) {
    // Redis removal logic
    return { success: true };
  }

  async getSmartSubstitutes(productId: string, branchId: string) {
    const product = await this.prisma.product.findUnique({
      where: { id: productId },
    });

    if (!product || !product.activeIngredient) {
      throw new NotFoundException('Product not found or has no active ingredient');
    }

    // Find substitutes with same active ingredient
    const substitutes = await this.prisma.product.findMany({
      where: {
        activeIngredient: product.activeIngredient,
        id: { not: productId },
        isActive: true,
      },
      include: {
        inventory: {
          where: { branchId },
        },
      },
    });

    // AI ranking: profitability first, then price
    return this.aiService.rankSubstitutes(substitutes, branchId);
  }

  async processCheckout(checkoutDto: CheckoutDto, branchId: string, userId: string) {
    const { items = [], paymentMethod, paidAmount, customerId } = checkoutDto;

    if (!items || items.length === 0) {
      throw new BadRequestException('Cart is empty');
    }

    // Use transaction for data consistency
    const sale = await this.prisma.$transaction(async (tx) => {
      // Generate invoice number
      const invoiceNumber = `INV-${Date.now()}-${Math.random().toString(36).substr(2, 4).toUpperCase()}`;

      // Calculate totals
      let totalAmount = 0;
      let discountAmount = 0;

      for (const item of items) {
        const product = await tx.product.findUnique({
          where: { id: item.productId },
        });

        if (!product) {
          throw new NotFoundException(`Product ${item.productId} not found`);
        }

        const itemTotal = product.sellingPrice * item.quantity;
        totalAmount += itemTotal;
        discountAmount += item.discount || 0;

        // Update inventory (FIFO)
        const inventory = await tx.inventory.findFirst({
          where: {
            productId: item.productId,
            branchId,
            quantity: { gt: 0 },
          },
          orderBy: { expiryDate: 'asc' },
        });

        if (!inventory || inventory.quantity < item.quantity) {
          throw new BadRequestException(`Insufficient stock for ${product.name}`);
        }

        await tx.inventory.update({
          where: { id: inventory.id },
          data: { quantity: { decrement: item.quantity } },
        });

        // Record stock movement
        await tx.stockMovement.create({
          data: {
            productId: item.productId,
            branchId,
            type: 'SALE',
            quantity: -item.quantity,
            referenceId: invoiceNumber,
          },
        });
      }

      // Create sale
      const createSale = await tx.sale.create({
        data: {
          companyId: (await tx.user.findUnique({ where: { id: userId } })).companyId || '',
          branchId,
          userId,
          customerId,
          invoiceNumber,
          totalAmount,
          discountAmount,
          finalAmount: totalAmount - discountAmount,
          paidAmount: paidAmount || totalAmount - discountAmount,
          paymentMethod: paymentMethod as any,
          items: {
            create: items.map(item => ({
              productId: item.productId,
              quantity: item.quantity,
              unitPrice: (product as any).sellingPrice,
              discount: item.discount || 0,
              totalPrice: (product as any).sellingPrice * item.quantity - (item.discount || 0),
              batchNumber: item.batchNumber,
            })),
          },
        },
      });

      // Award loyalty points if customer exists
      if (customerId) {
        await tx.customer.update({
          where: { id: customerId },
          data: {
            loyaltyPoints: {
              increment: Math.floor((totalAmount - discountAmount) / 10), // 1 point per 10 SAR
            },
          },
        });
      }

      return createSale;
    });

    return {
      success: true,
      sale,
      invoice: sale.invoiceNumber,
    };
  }

  async processPrescriptionOCR(imageUrl: string) {
    // Call AI OCR service
    const ocrResult = await this.aiService.processPrescriptionOCR(imageUrl);
    
    const cartItems = [];
    for (const item of ocrResult.medicines) {
      const product = await this.prisma.product.findFirst({
        where: {
          OR: [
            { name: { contains: item.name, mode: 'insensitive' } },
            { barcode: item.barcode },
          ],
        },
      });

      if (product) {
        cartItems.push({
          productId: product.id,
          name: product.name,
          quantity: item.quantity,
          dosage: item.dosage,
        });

        // Check DDI alerts
        const alerts = await this.aiService.checkDrugInteractions(product.id);
        if (alerts.length > 0) {
          cartItems[cartItems.length - 1].alerts = alerts;
        }
      }
    }

    return {
      medicines: cartItems,
      parsed: true,
    };
  }

  async quickSaleLookup() {
    // Fast lookup for most popular items
    return this.prisma.product.findMany({
      where: { isActive: true },
      take: 50,
      orderBy: { updatedAt: 'desc' },
    });
  }
}