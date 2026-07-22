import { PrismaService } from '../prisma/prisma.service';
import { CreateStockDto } from './dto/create-stock.dto';
import { UpdateStockDto } from './dto/update-stock.dto';
export declare class InventoryService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    createStock(dto: CreateStockDto): Promise<{
        id: number;
        createdAt: Date;
        updatedAt: Date;
        branchId: number;
        productId: number;
        quantity: number;
        batchNumber: string | null;
        expiryDate: Date | null;
    }>;
    updateStock(id: number, dto: UpdateStockDto): Promise<{
        id: number;
        createdAt: Date;
        updatedAt: Date;
        branchId: number;
        productId: number;
        quantity: number;
        batchNumber: string | null;
        expiryDate: Date | null;
    }>;
    getStocksByBranch(branchId: number): Promise<{
        id: number;
        createdAt: Date;
        updatedAt: Date;
        branchId: number;
        productId: number;
        quantity: number;
        batchNumber: string | null;
        expiryDate: Date | null;
    }[]>;
    decreaseQuantity(productId: number, branchId: number, amount: number): Promise<{
        id: number;
        createdAt: Date;
        updatedAt: Date;
        branchId: number;
        productId: number;
        quantity: number;
        batchNumber: string | null;
        expiryDate: Date | null;
    }>;
}
