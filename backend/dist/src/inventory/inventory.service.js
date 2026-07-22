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
exports.InventoryService = void 0;
const common_1 = require("@nestjs/common");
const prisma_service_1 = require("../prisma/prisma.service");
let InventoryService = class InventoryService {
    prisma;
    constructor(prisma) {
        this.prisma = prisma;
    }
    async createStock(dto) {
        const { productId, branchId, quantity, batchNumber, expiryDate } = dto;
        const product = await this.prisma.product.findUnique({ where: { id: productId } });
        if (!product) {
            throw new common_1.NotFoundException('Product not found');
        }
        const branch = await this.prisma.branch.findUnique({ where: { id: branchId } });
        if (!branch) {
            throw new common_1.NotFoundException('Branch not found');
        }
        return this.prisma.stock.create({
            data: { productId, branchId, quantity, batchNumber, expiryDate },
        });
    }
    async updateStock(id, dto) {
        const stock = await this.prisma.stock.findUnique({ where: { id } });
        if (!stock) {
            throw new common_1.NotFoundException('Stock entry not found');
        }
        return this.prisma.stock.update({ where: { id }, data: dto });
    }
    async getStocksByBranch(branchId) {
        return this.prisma.stock.findMany({ where: { branchId } });
    }
    async decreaseQuantity(productId, branchId, amount) {
        const stock = await this.prisma.stock.findFirst({
            where: { productId, branchId },
        });
        if (!stock || stock.quantity < amount) {
            throw new common_1.NotFoundException('Insufficient stock');
        }
        return this.prisma.stock.update({
            where: { id: stock.id },
            data: { quantity: stock.quantity - amount },
        });
    }
};
exports.InventoryService = InventoryService;
exports.InventoryService = InventoryService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService])
], InventoryService);
//# sourceMappingURL=inventory.service.js.map