"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.PosService = void 0;
const common_1 = require("@nestjs/common");
const prisma_service_1 = require("../prisma/prisma.service");
const ai_service_1 = require("../ai/ai.service");
const inventory_service_1 = require("../inventory/inventory.service");
let PosService = class PosService {
    prisma;
    aiService;
    inventoryService;
    constructor(prisma, aiService, inventoryService) {
        this.prisma = prisma;
        this.aiService = aiService;
        this.inventoryService = inventoryService;
    }
    async addToCart(userId, dto) {
        const product = await this.prisma.product.findUnique({ where: { id: dto.productId } });
        if (!product) {
            throw new common_1.BadRequestException('Product not found');
        }
        if (Number(product.price) <= 0) {
            throw new common_1.BadRequestException('Invalid product price');
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
    async checkout(userId, dto) {
        const items = await this.prisma.cartItem.findMany({ where: { userId } });
        if (items.length === 0) {
            throw new common_1.BadRequestException('Cart is empty');
        }
        const total = items.reduce((sum, i) => sum + Number(i.price) * i.quantity, 0);
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
        for (const item of items) {
            await this.inventoryService.decreaseQuantity(item.productId, dto.branchId, item.quantity);
        }
        await this.prisma.cartItem.deleteMany({ where: { userId } });
        return sale;
    }
    async uploadPrescription(userId, dto) {
        const { filePath, patientId } = dto;
        const prescription = await this.prisma.prescription.create({
            data: { patientId, imageUrl: filePath, processed: false },
        });
        const ocrResult = await this.aiService.performOcr(filePath);
        await this.prisma.prescription.update({
            where: { id: prescription.id },
            data: { processed: true },
        });
        return { prescriptionId: prescription.id, ocrResult };
    }
};
exports.PosService = PosService;
exports.PosService = PosService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService,
        ai_service_1.AiService,
        inventory_service_1.InventoryService])
], PosService);
//# sourceMappingURL=pos.service.js.map