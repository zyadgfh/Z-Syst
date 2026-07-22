import { InventoryService } from './inventory.service';
import { CreateStockDto } from './dto/create-stock.dto';
import { UpdateStockDto } from './dto/update-stock.dto';
export declare class InventoryController {
    private readonly inventoryService;
    constructor(inventoryService: InventoryService);
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
    getStockByBranch(branchId: number): Promise<{
        id: number;
        createdAt: Date;
        updatedAt: Date;
        branchId: number;
        productId: number;
        quantity: number;
        batchNumber: string | null;
        expiryDate: Date | null;
    }[]>;
}
