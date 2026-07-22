import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';

@Injectable()
export class ProductsService {
  constructor(private readonly prisma: PrismaService) {}

  async getProducts(branchId: number) {
    const [products, stockEntries] = await Promise.all([
      this.prisma.product.findMany({
        orderBy: { createdAt: 'desc' },
      }),
      this.prisma.stock.findMany({
        where: { branchId },
      }),
    ]);

    const stockByProduct = new Map<number, number>();
    stockEntries.forEach((entry) => {
      stockByProduct.set(entry.productId, entry.quantity);
    });

    return products.map((product) => ({
      id: product.id,
      sku: product.sku,
      name: product.name,
      description: product.description ?? '',
      price: Number(product.price),
      category: 'General',
      stock: stockByProduct.get(product.id) ?? 0,
    }));
  }
}
