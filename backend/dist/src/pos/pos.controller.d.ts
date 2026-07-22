import { PosService } from './pos.service';
import { AddToCartDto } from './dto/add-to-cart.dto';
import { CheckoutDto } from './dto/checkout.dto';
import { UploadPrescriptionDto } from './dto/upload-prescription.dto';
export declare class PosController {
    private readonly posService;
    constructor(posService: PosService);
    addToCart(req: any, dto: AddToCartDto): Promise<{
        id: number;
        createdAt: Date;
        productId: number;
        quantity: number;
        price: import("@prisma/client-runtime-utils").Decimal;
        userId: number;
    }>;
    checkout(req: any, dto: CheckoutDto): Promise<{
        id: number;
        createdAt: Date;
        branchId: number;
        userId: number | null;
        totalAmount: import("@prisma/client-runtime-utils").Decimal;
        status: string;
    }>;
    uploadPrescription(req: any, dto: UploadPrescriptionDto): Promise<{
        prescriptionId: number;
        ocrResult: string;
    }>;
}
