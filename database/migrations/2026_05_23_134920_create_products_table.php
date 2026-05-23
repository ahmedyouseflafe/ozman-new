    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::create('products', function (Blueprint $table) {

                $table->id();

                $table->foreignId('shop_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('category_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('name');

                $table->string('slug')->unique();

                $table->longText('description')->nullable();

                $table->decimal('price', 10, 2);

                $table->decimal('discount_price', 10, 2)->nullable();

                $table->integer('quantity')->default(0);

                $table->string('sku')->nullable();

                $table->string('barcode')->nullable();

                $table->string('main_image')->nullable();

                $table->string('video')->nullable();

                $table->integer('views')->default(0);

                $table->float('rating')->default(0);

                $table->boolean('is_featured')->default(false);

                $table->boolean('is_active')->default(true);

                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('products');
        }
    };
