<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega índices completos en todas las tablas importantes para optimizar:
     * - Búsquedas de participantes
     * - Consultas de programas y cursos
     * - Reportes administrativos
     * - Analytics y logs
     * - Relaciones y joins frecuentes
     */
    public function up(): void
    {
        // ============================================
        // PARTICIPANTS - Tabla principal de participantes
        // ============================================
        Schema::table('participants', function (Blueprint $table) {
            // Para búsquedas por RUT (document_number ya tiene index pero agregamos is_active)
            $table->index(['document_number', 'is_active'], 'idx_participants_doc_active');

            // Para filtros por estado activo
            $table->index('is_active', 'idx_participants_is_active');

            // Para búsquedas por nombre (queries LIKE)
            $table->index('first_name', 'idx_participants_first_name');
            $table->index('first_last_name', 'idx_participants_first_last_name');

            // Para búsquedas por teléfono
            $table->index('phone', 'idx_participants_phone');

            // Para reportes por fecha de nacimiento
            $table->index('birth_date', 'idx_participants_birth_date');

            // Para filtros combinados status + email
            $table->index(['status', 'email'], 'idx_participants_status_email');
        });

        // ============================================
        // PARTICIPANT_COURSE - Relación participante-curso
        // ============================================
        Schema::table('participant_course', function (Blueprint $table) {
            // Para filtros por status
            $table->index('status', 'idx_participant_course_status');

            // Para reportes por fecha
            $table->index('created_at', 'idx_participant_course_created_at');

            // Para consultas combinadas
            $table->index(['status', 'created_at'], 'idx_participant_course_status_date');
        });

        // ============================================
        // PARTICIPANT_PROGRAM - Relación participante-programa
        // ============================================
        Schema::table('participant_program', function (Blueprint $table) {
            // Para filtros por is_active (si existe la columna)
            if (Schema::hasColumn('participant_program', 'is_active')) {
                $table->index('is_active', 'idx_participant_program_is_active');
                $table->index(['is_active', 'status'], 'idx_participant_program_active_status');
            }

            // Para búsquedas de participantes en un programa específico activos
            $table->index(['program_id', 'status'], 'idx_participant_program_prog_status');
        });

        // ============================================
        // PROGRAMS - Plantillas de programas
        // ============================================
        Schema::table('programs', function (Blueprint $table) {
            // Para búsquedas por nombre (LIKE queries)
            $table->index('name', 'idx_programs_name');

            // Para filtros combinados
            $table->index(['active', 'destination'], 'idx_programs_active_destination');

            // Para auditoría
            $table->index('created_by', 'idx_programs_created_by');
        });

        // ============================================
        // PROGRAM_COURSES - Cursos específicos de programas
        // ============================================
        Schema::table('program_courses', function (Blueprint $table) {
            // Para filtros por año
            $table->index('year', 'idx_program_courses_year');

            // Para auditoría
            $table->index('created_by', 'idx_program_courses_created_by');

            // Para filtros combinados año + status
            $table->index(['year', 'status'], 'idx_program_courses_year_status');

            // Para búsquedas por ejecutivo de ventas
            $table->index('sales_executive_id', 'idx_program_courses_sales_exec');

            // Para consultas de programas con pagos habilitados
            $table->index(['enable_total_payment', 'active'], 'idx_program_courses_payment_enabled');
        });

        // ============================================
        // COURSES - Cursos de instituciones
        // ============================================
        Schema::table('courses', function (Blueprint $table) {
            // Para consultas combinadas institución + status
            $table->index(['institution_id', 'status'], 'idx_courses_institution_status');

            // Para filtros por nivel educativo
            $table->index('education_level', 'idx_courses_education_level');

            // Para filtros por año
            $table->index('year', 'idx_courses_year');

            // Para consultas combinadas año + nivel
            $table->index(['year', 'education_level'], 'idx_courses_year_level');

            // Para filtros por creador
            $table->index('created_by', 'idx_courses_created_by');
        });

        // ============================================
        // INSTITUTIONS - Instituciones educativas
        // ============================================
        Schema::table('institutions', function (Blueprint $table) {
            // Para filtros por tipo
            $table->index('type', 'idx_institutions_type');

            // Para búsquedas por email
            $table->index('email', 'idx_institutions_email');

            // Para filtros combinados tipo + activo
            $table->index(['type', 'active'], 'idx_institutions_type_active');

            // Para búsquedas por código (si existe)
            if (Schema::hasColumn('institutions', 'code')) {
                $table->index('code', 'idx_institutions_code');
            }

            // Para auditoría (si existe)
            if (Schema::hasColumn('institutions', 'created_by')) {
                $table->index('created_by', 'idx_institutions_created_by');
            }
        });

        // ============================================
        // PARTICIPANT_PROGRAM_DISCOUNTS - Descuentos
        // ============================================
        Schema::table('participant_program_discounts', function (Blueprint $table) {
            // Para auditoría
            $table->index('approved_by', 'idx_ppd_approved_by');

            // Para filtros por tipo de descuento
            $table->index('discount_type', 'idx_ppd_discount_type');

            // Para reportes por fecha
            $table->index('created_at', 'idx_ppd_created_at');

            // Para consultas combinadas
            $table->index(['discount_type', 'created_at'], 'idx_ppd_type_date');
        });

        // ============================================
        // ORDERS_DETAIL - Detalles de órdenes
        // ============================================
        Schema::table('orders_detail', function (Blueprint $table) {
            // Para búsquedas por nombre de comprador
            $table->index('name', 'idx_orders_detail_name');

            // Para filtros por gateway
            $table->index('payment_gateway_id', 'idx_orders_detail_gateway');

            // Para filtros combinados gateway + status
            $table->index(['payment_gateway_id', 'status'], 'idx_orders_detail_gateway_status');

            // Para consultas de términos aceptados
            if (Schema::hasColumn('orders_detail', 'terms_accepted')) {
                $table->index('terms_accepted', 'idx_orders_detail_terms');
            }

            // Para filtros por país/región/ciudad
            if (Schema::hasColumn('orders_detail', 'country')) {
                $table->index('country', 'idx_orders_detail_country');
            }
        });

        // ============================================
        // ADMIN_LOGS - Logs administrativos
        // ============================================
        Schema::table('admin_logs', function (Blueprint $table) {
            // Para búsquedas específicas de recursos
            $table->index(['resource_type', 'resource_id'], 'idx_admin_logs_resource');

            // Para reportes de módulo + acción
            $table->index(['module', 'action'], 'idx_admin_logs_module_action');

            // Para búsquedas por IP
            $table->index('ip_address', 'idx_admin_logs_ip');

            // Para consultas por sesión
            $table->index('session_id', 'idx_admin_logs_session');
        });

        // ============================================
        // ECOMMERCE_ANALYTICS - Analytics de ecommerce
        // ============================================
        // Ya tiene buenos índices, solo agregamos algunos específicos
        Schema::table('ecommerce_analytics', function (Blueprint $table) {
            // Para análisis de conversión
            if (Schema::hasColumn('ecommerce_analytics', 'payment_completed_at')) {
                $table->index('payment_completed_at', 'idx_ecommerce_payment_completed');
            }

            // Para análisis de fallos
            if (Schema::hasColumn('ecommerce_analytics', 'payment_failed_at')) {
                $table->index('payment_failed_at', 'idx_ecommerce_payment_failed');
            }
        });

        // ============================================
        // VIRTUALPOS_PLANS - Planes de VirtualPos
        // ============================================
        if (Schema::hasTable('virtualpos_plans')) {
            Schema::table('virtualpos_plans', function (Blueprint $table) {
                // Para búsquedas por plan_id
                if (Schema::hasColumn('virtualpos_plans', 'plan_id')) {
                    $table->index('plan_id', 'idx_virtualpos_plans_plan_id');
                }

                // Para consultas por programa
                if (Schema::hasColumn('virtualpos_plans', 'program_course_id')) {
                    $table->index('program_course_id', 'idx_virtualpos_plans_program');
                }

                // Para consultas por estado
                if (Schema::hasColumn('virtualpos_plans', 'status')) {
                    $table->index('status', 'idx_virtualpos_plans_status');
                }
            });
        }

        // ============================================
        // PAYMENT_CONFIRMATION_LOGS - Logs de confirmación
        // ============================================
        if (Schema::hasTable('payment_confirmation_logs')) {
            Schema::table('payment_confirmation_logs', function (Blueprint $table) {
                // Para búsquedas por payment_id
                if (Schema::hasColumn('payment_confirmation_logs', 'payment_id')) {
                    $table->index('payment_id', 'idx_payment_conf_logs_payment');
                }

                // Para búsquedas por order_id
                if (Schema::hasColumn('payment_confirmation_logs', 'order_id')) {
                    $table->index('order_id', 'idx_payment_conf_logs_order');
                }

                // Para filtros por status
                if (Schema::hasColumn('payment_confirmation_logs', 'status')) {
                    $table->index('status', 'idx_payment_conf_logs_status');
                }

                // Para reportes por fecha
                $table->index('created_at', 'idx_payment_conf_logs_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PARTICIPANTS
        Schema::table('participants', function (Blueprint $table) {
            $table->dropIndex('idx_participants_doc_active');
            $table->dropIndex('idx_participants_is_active');
            $table->dropIndex('idx_participants_first_name');
            $table->dropIndex('idx_participants_first_last_name');
            $table->dropIndex('idx_participants_phone');
            $table->dropIndex('idx_participants_birth_date');
            $table->dropIndex('idx_participants_status_email');
        });

        // PARTICIPANT_COURSE
        Schema::table('participant_course', function (Blueprint $table) {
            $table->dropIndex('idx_participant_course_status');
            $table->dropIndex('idx_participant_course_created_at');
            $table->dropIndex('idx_participant_course_status_date');
        });

        // PARTICIPANT_PROGRAM
        Schema::table('participant_program', function (Blueprint $table) {
            if (Schema::hasColumn('participant_program', 'is_active')) {
                $table->dropIndex('idx_participant_program_is_active');
                $table->dropIndex('idx_participant_program_active_status');
            }
            $table->dropIndex('idx_participant_program_prog_status');
        });

        // PROGRAMS
        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex('idx_programs_name');
            $table->dropIndex('idx_programs_active_destination');
            $table->dropIndex('idx_programs_created_by');
        });

        // PROGRAM_COURSES
        Schema::table('program_courses', function (Blueprint $table) {
            $table->dropIndex('idx_program_courses_year');
            $table->dropIndex('idx_program_courses_created_by');
            $table->dropIndex('idx_program_courses_year_status');
            $table->dropIndex('idx_program_courses_sales_exec');
            $table->dropIndex('idx_program_courses_payment_enabled');
        });

        // COURSES
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('idx_courses_institution_status');
            $table->dropIndex('idx_courses_education_level');
            $table->dropIndex('idx_courses_year');
            $table->dropIndex('idx_courses_year_level');
            $table->dropIndex('idx_courses_created_by');
        });

        // INSTITUTIONS
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropIndex('idx_institutions_type');
            $table->dropIndex('idx_institutions_email');
            $table->dropIndex('idx_institutions_type_active');
            if (Schema::hasColumn('institutions', 'code')) {
                $table->dropIndex('idx_institutions_code');
            }
            if (Schema::hasColumn('institutions', 'created_by')) {
                $table->dropIndex('idx_institutions_created_by');
            }
        });

        // PARTICIPANT_PROGRAM_DISCOUNTS
        Schema::table('participant_program_discounts', function (Blueprint $table) {
            $table->dropIndex('idx_ppd_approved_by');
            $table->dropIndex('idx_ppd_discount_type');
            $table->dropIndex('idx_ppd_created_at');
            $table->dropIndex('idx_ppd_type_date');
        });

        // ORDERS_DETAIL
        Schema::table('orders_detail', function (Blueprint $table) {
            $table->dropIndex('idx_orders_detail_name');
            $table->dropIndex('idx_orders_detail_gateway');
            $table->dropIndex('idx_orders_detail_gateway_status');
            if (Schema::hasColumn('orders_detail', 'terms_accepted')) {
                $table->dropIndex('idx_orders_detail_terms');
            }
            if (Schema::hasColumn('orders_detail', 'country')) {
                $table->dropIndex('idx_orders_detail_country');
            }
        });

        // ADMIN_LOGS
        Schema::table('admin_logs', function (Blueprint $table) {
            $table->dropIndex('idx_admin_logs_resource');
            $table->dropIndex('idx_admin_logs_module_action');
            $table->dropIndex('idx_admin_logs_ip');
            $table->dropIndex('idx_admin_logs_session');
        });

        // ECOMMERCE_ANALYTICS
        Schema::table('ecommerce_analytics', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_analytics', 'payment_completed_at')) {
                $table->dropIndex('idx_ecommerce_payment_completed');
            }
            if (Schema::hasColumn('ecommerce_analytics', 'payment_failed_at')) {
                $table->dropIndex('idx_ecommerce_payment_failed');
            }
        });

        // VIRTUALPOS_PLANS
        if (Schema::hasTable('virtualpos_plans')) {
            Schema::table('virtualpos_plans', function (Blueprint $table) {
                if (Schema::hasColumn('virtualpos_plans', 'plan_id')) {
                    $table->dropIndex('idx_virtualpos_plans_plan_id');
                }
                if (Schema::hasColumn('virtualpos_plans', 'program_course_id')) {
                    $table->dropIndex('idx_virtualpos_plans_program');
                }
                if (Schema::hasColumn('virtualpos_plans', 'status')) {
                    $table->dropIndex('idx_virtualpos_plans_status');
                }
            });
        }

        // PAYMENT_CONFIRMATION_LOGS
        if (Schema::hasTable('payment_confirmation_logs')) {
            Schema::table('payment_confirmation_logs', function (Blueprint $table) {
                if (Schema::hasColumn('payment_confirmation_logs', 'payment_id')) {
                    $table->dropIndex('idx_payment_conf_logs_payment');
                }
                if (Schema::hasColumn('payment_confirmation_logs', 'order_id')) {
                    $table->dropIndex('idx_payment_conf_logs_order');
                }
                if (Schema::hasColumn('payment_confirmation_logs', 'status')) {
                    $table->dropIndex('idx_payment_conf_logs_status');
                }
                $table->dropIndex('idx_payment_conf_logs_date');
            });
        }
    }
};
