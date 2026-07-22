import { PrismaService } from '../prisma/prisma.service';
export declare class ProductsService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    getProducts(branchId: number): Promise<{
        id: number;
        sku: string;
        name: string;
        description: string;
        price: number;
        category: string;
        stock: number;
    }[]>;
}
