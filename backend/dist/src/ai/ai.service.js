"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
var AiService_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.AiService = void 0;
const common_1 = require("@nestjs/common");
const axios_1 = __importDefault(require("axios"));
const FormData = require('form-data');
const fs_1 = require("fs");
let AiService = AiService_1 = class AiService {
    logger = new common_1.Logger(AiService_1.name);
    async performOcr(imagePath) {
        const ocrUrl = process.env.OCR_SERVICE_URL;
        if (!ocrUrl) {
            this.logger.error('OCR_SERVICE_URL not set');
            throw new Error('OCR service URL not configured');
        }
        const form = new FormData();
        form.append('file', (0, fs_1.readFileSync)(imagePath));
        const response = await axios_1.default.post(ocrUrl, form, {
            headers: form.getHeaders(),
        });
        return response.data?.text || '';
    }
    async forecastDemand(data) {
        return {};
    }
};
exports.AiService = AiService;
exports.AiService = AiService = AiService_1 = __decorate([
    (0, common_1.Injectable)()
], AiService);
//# sourceMappingURL=ai.service.js.map