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
var __param = (this && this.__param) || function (paramIndex, decorator) {
    return function (target, key) { decorator(target, key, paramIndex); }
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.PosController = void 0;
const common_1 = require("@nestjs/common");
const swagger_1 = require("@nestjs/swagger");
const pos_service_1 = require("./pos.service");
const add_to_cart_dto_1 = require("./dto/add-to-cart.dto");
const checkout_dto_1 = require("./dto/checkout.dto");
const upload_prescription_dto_1 = require("./dto/upload-prescription.dto");
const jwt_auth_guard_1 = require("../auth/jwt-auth.guard");
let PosController = class PosController {
    posService;
    constructor(posService) {
        this.posService = posService;
    }
    async addToCart(req, dto) {
        const userId = req.user.userId;
        return this.posService.addToCart(userId, dto);
    }
    async checkout(req, dto) {
        const userId = req.user.userId;
        return this.posService.checkout(userId, dto);
    }
    async uploadPrescription(req, dto) {
        const userId = req.user.userId;
        return this.posService.uploadPrescription(userId, dto);
    }
};
exports.PosController = PosController;
__decorate([
    (0, common_1.UseGuards)(jwt_auth_guard_1.JwtAuthGuard),
    (0, swagger_1.ApiBearerAuth)(),
    (0, common_1.Post)('cart'),
    (0, swagger_1.ApiOperation)({ summary: 'Add a product to the user cart' }),
    (0, swagger_1.ApiResponse)({ status: 201, description: 'Cart item added' }),
    __param(0, (0, common_1.Request)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, add_to_cart_dto_1.AddToCartDto]),
    __metadata("design:returntype", Promise)
], PosController.prototype, "addToCart", null);
__decorate([
    (0, common_1.UseGuards)(jwt_auth_guard_1.JwtAuthGuard),
    (0, swagger_1.ApiBearerAuth)(),
    (0, common_1.Post)('checkout'),
    (0, swagger_1.ApiOperation)({ summary: 'Checkout the user cart' }),
    (0, swagger_1.ApiResponse)({ status: 201, description: 'Checkout completed, sale created' }),
    __param(0, (0, common_1.Request)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, checkout_dto_1.CheckoutDto]),
    __metadata("design:returntype", Promise)
], PosController.prototype, "checkout", null);
__decorate([
    (0, common_1.UseGuards)(jwt_auth_guard_1.JwtAuthGuard),
    (0, swagger_1.ApiBearerAuth)(),
    (0, common_1.Post)('prescription'),
    (0, swagger_1.ApiOperation)({ summary: 'Upload prescription image for OCR processing' }),
    (0, swagger_1.ApiResponse)({ status: 201, description: 'Prescription saved with OCR result' }),
    __param(0, (0, common_1.Request)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, upload_prescription_dto_1.UploadPrescriptionDto]),
    __metadata("design:returntype", Promise)
], PosController.prototype, "uploadPrescription", null);
exports.PosController = PosController = __decorate([
    (0, swagger_1.ApiTags)('POS'),
    (0, common_1.Controller)('pos'),
    __metadata("design:paramtypes", [pos_service_1.PosService])
], PosController);
//# sourceMappingURL=pos.controller.js.map