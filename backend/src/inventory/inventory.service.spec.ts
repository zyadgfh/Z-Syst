import { Test, TestingModule } from '@nestjs/testing';
import { InventoryService } from './inventory.service';
import { PrismaService } from '../prisma/prisma.service';
import { NotFoundException } from '@nestjs/common';

const mockPrisma = {
  product: { findUnique: jest.fn() },
  branch: { findUnique: jest.fn() },
  stock: {
    create: jest.fn(),
    findUnique: jest.fn(),
    findFirst: jest.fn(),
    findMany: jest.fn(),
    update: jest.fn(),
  },
};

describe('InventoryService', () => {
  let service: InventoryService;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [
        InventoryService,
        { provide: PrismaService, useValue: mockPrisma },
      ],
    }).compile();

    service = module.get<InventoryService>(InventoryService);
    jest.clearAllMocks();
  });

  describe('createStock', () => {
    it('should create a stock entry', async () => {
      mockPrisma.product.findUnique.mockResolvedValue({ id: 1 });
      mockPrisma.branch.findUnique.mockResolvedValue({ id: 1 });
      mockPrisma.stock.create.mockResolvedValue({ id: 1, quantity: 100 });

      const dto = {
        productId: 1,
        branchId: 1,
        quantity: 100,
        batchNumber: 'B001',
        expiryDate: '2027-01-01',
      };
      const result = await service.createStock(dto);
      expect(result).toEqual({ id: 1, quantity: 100 });
      expect(mockPrisma.stock.create).toHaveBeenCalledTimes(1);
    });

    it('should throw NotFoundException when product not found', async () => {
      mockPrisma.product.findUnique.mockResolvedValue(null);
      await expect(
        service.createStock({ productId: 99, branchId: 1, quantity: 10 }),
      ).rejects.toThrow(NotFoundException);
    });

    it('should throw NotFoundException when branch not found', async () => {
      mockPrisma.product.findUnique.mockResolvedValue({ id: 1 });
      mockPrisma.branch.findUnique.mockResolvedValue(null);
      await expect(
        service.createStock({ productId: 1, branchId: 99, quantity: 10 }),
      ).rejects.toThrow(NotFoundException);
    });
  });

  describe('updateStock', () => {
    it('should update a stock entry', async () => {
      mockPrisma.stock.findUnique.mockResolvedValue({ id: 1 });
      mockPrisma.stock.update.mockResolvedValue({ id: 1, quantity: 200 });

      const result = await service.updateStock(1, { quantity: 200 });
      expect(result.quantity).toBe(200);
    });

    it('should throw NotFoundException when stock not found', async () => {
      mockPrisma.stock.findUnique.mockResolvedValue(null);
      await expect(service.updateStock(999, { quantity: 5 })).rejects.toThrow(NotFoundException);
    });
  });

  describe('getStocksByBranch', () => {
    it('should return all stocks for a branch', async () => {
      mockPrisma.stock.findMany.mockResolvedValue([{ id: 1 }, { id: 2 }]);
      const result = await service.getStocksByBranch(1);
      expect(result).toHaveLength(2);
    });
  });

  describe('decreaseQuantity', () => {
    it('should decrease stock quantity', async () => {
      mockPrisma.stock.findFirst.mockResolvedValue({ id: 1, quantity: 50 });
      mockPrisma.stock.update.mockResolvedValue({ id: 1, quantity: 40 });

      const result = await service.decreaseQuantity(1, 1, 10);
      expect(result.quantity).toBe(40);
      expect(mockPrisma.stock.update).toHaveBeenCalledWith({
        where: { id: 1 },
        data: { quantity: 40 },
      });
    });

    it('should throw NotFoundException for insufficient stock', async () => {
      mockPrisma.stock.findFirst.mockResolvedValue({ id: 1, quantity: 5 });
      await expect(service.decreaseQuantity(1, 1, 10)).rejects.toThrow(NotFoundException);
    });

    it('should throw NotFoundException when no stock entry found', async () => {
      mockPrisma.stock.findFirst.mockResolvedValue(null);
      await expect(service.decreaseQuantity(1, 1, 1)).rejects.toThrow(NotFoundException);
    });
  });
});
