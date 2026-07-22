import { PrismaService } from '../prisma/prisma.service';
import { AiService } from '../ai/ai.service';
import { InventoryService } from '../inventory/inventory.service';
import { AddToCartDto } from './dto/add-to-cart.dto';
import { CheckoutDto } from './dto/checkout.dto';
import { UploadPrescriptionDto } from './dto/upload-prescription.dto';
export declare class PosService {
    private readonly prisma;
    private readonly aiService;
    private readonly inventoryService;
    constructor(prisma: PrismaService, aiService: AiService, inventoryService: InventoryService);
    addToCart(userId: number, dto: AddToCartDto): Promise<{
        id: number;
        createdAt: Date;
        productId: number;
        quantity: number;
        price: import("@prisma/client-runtime-utils").Decimal;
        userId: number;
    }>;
    checkout(userId: number, dto: CheckoutDto): Promise<{
        id: number;
        createdAt: Date;
        branchId: number;
        userId: number | null;
        totalAmount: import("@prisma/client-runtime-utils").Decimal;
        status: string;
    }>;
    uploadPrescription(userId: number, dto: UploadPrescriptionDto): Promise<{
        prescriptionId: number;
        ocrResult: string;
    }>;
}
