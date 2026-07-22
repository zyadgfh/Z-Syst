import { PrismaService } from '../prisma/prisma.service';
import { JwtService } from '@nestjs/jwt';
import { RegisterDto } from './dto/register.dto';
import { LoginDto } from './dto/login.dto';
export declare class AuthService {
    private readonly prisma;
    private readonly jwt;
    constructor(prisma: PrismaService, jwt: JwtService);
    register(data: RegisterDto): Promise<{
        user: {
            email: string;
            password: string;
            role: import("@prisma/client").$Enums.Role;
            id: number;
            createdAt: Date;
            updatedAt: Date;
            branchId: number | null;
        };
        token: string;
    }>;
    login(data: LoginDto): Promise<{
        user: {
            email: string;
            password: string;
            role: import("@prisma/client").$Enums.Role;
            id: number;
            createdAt: Date;
            updatedAt: Date;
            branchId: number | null;
        };
        token: string;
    }>;
}
