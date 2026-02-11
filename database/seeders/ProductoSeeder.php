<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = Categoria::all();

        if ($categorias->isEmpty()) {
            $this->command->warn('No hay categorías. Ejecuta primero el CategoriaSeeder.');
            return;
        }

        $productosData = [
            'Analgésicos' => [
                ['nombre' => 'Paracetamol 500mg', 'laboratorio' => 'Bayer', 'concentracion' => '500mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Ibuprofeno 400mg', 'laboratorio' => 'Pfizer', 'concentracion' => '400mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Acetaminofén 750mg', 'laboratorio' => 'Genfar', 'concentracion' => '750mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Naproxeno 250mg', 'laboratorio' => 'Abbott', 'concentracion' => '250mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Diclofenaco 50mg', 'laboratorio' => 'Novartis', 'concentracion' => '50mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Ketorolaco 10mg', 'laboratorio' => 'Roche', 'concentracion' => '10mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Aspirina 100mg', 'laboratorio' => 'Bayer', 'concentracion' => '100mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Metamizol 500mg', 'laboratorio' => 'Sanofi', 'concentracion' => '500mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Celecoxib 200mg', 'laboratorio' => 'Pfizer', 'concentracion' => '200mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Meloxicam 15mg', 'laboratorio' => 'Abbott', 'concentracion' => '15mg', 'presentacion' => 'Tabletas'],
            ],
            'Antibióticos' => [
                ['nombre' => 'Amoxicilina 500mg', 'laboratorio' => 'GlaxoSmithKline', 'concentracion' => '500mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Azitromicina 500mg', 'laboratorio' => 'Pfizer', 'concentracion' => '500mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Ciprofloxacino 500mg', 'laboratorio' => 'Bayer', 'concentracion' => '500mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Cefalexina 500mg', 'laboratorio' => 'Abbott', 'concentracion' => '500mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Claritromicina 500mg', 'laboratorio' => 'Abbott', 'concentracion' => '500mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Levofloxacino 500mg', 'laboratorio' => 'Sanofi', 'concentracion' => '500mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Clindamicina 300mg', 'laboratorio' => 'Pfizer', 'concentracion' => '300mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Eritromicina 500mg', 'laboratorio' => 'Abbott', 'concentracion' => '500mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Doxiciclina 100mg', 'laboratorio' => 'Pfizer', 'concentracion' => '100mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Trimetoprima + Sulfametoxazol 160/800mg', 'laboratorio' => 'Roche', 'concentracion' => '160/800mg', 'presentacion' => 'Tabletas'],
            ],
            'Antiinflamatorios' => [
                ['nombre' => 'Ibuprofeno 600mg', 'laboratorio' => 'Pfizer', 'concentracion' => '600mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Diclofenaco Gel 1%', 'laboratorio' => 'Novartis', 'concentracion' => '1%', 'presentacion' => 'Gel tópico'],
                ['nombre' => 'Naproxeno 550mg', 'laboratorio' => 'Bayer', 'concentracion' => '550mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Dexametasona 4mg', 'laboratorio' => 'Pfizer', 'concentracion' => '4mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Prednisona 5mg', 'laboratorio' => 'Abbott', 'concentracion' => '5mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Ketoprofeno 100mg', 'laboratorio' => 'Sanofi', 'concentracion' => '100mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Piroxicam 20mg', 'laboratorio' => 'Pfizer', 'concentracion' => '20mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Betametasona 0.5mg', 'laboratorio' => 'Genfar', 'concentracion' => '0.5mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Etoricoxib 90mg', 'laboratorio' => 'Merck', 'concentracion' => '90mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Indometacina 25mg', 'laboratorio' => 'Abbott', 'concentracion' => '25mg', 'presentacion' => 'Cápsulas'],
            ],
            'Vitaminas y Suplementos' => [
                ['nombre' => 'Vitamina C 1000mg', 'laboratorio' => 'Nature Made', 'concentracion' => '1000mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Vitamina D3 2000 UI', 'laboratorio' => 'Nature Made', 'concentracion' => '2000 UI', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Complejo B', 'laboratorio' => 'Centrum', 'concentracion' => 'Variable', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Calcio + Vitamina D', 'laboratorio' => 'Centrum', 'concentracion' => '600mg/400UI', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Omega 3', 'laboratorio' => 'Nordic Naturals', 'concentracion' => '1000mg', 'presentacion' => 'Cápsulas blandas'],
                ['nombre' => 'Multivitamínico', 'laboratorio' => 'Centrum', 'concentracion' => 'Variable', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Hierro 65mg', 'laboratorio' => 'Genfar', 'concentracion' => '65mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Ácido Fólico 1mg', 'laboratorio' => 'Bayer', 'concentracion' => '1mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Magnesio 500mg', 'laboratorio' => 'Nature Made', 'concentracion' => '500mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Vitamina E 400 UI', 'laboratorio' => 'Nature Made', 'concentracion' => '400 UI', 'presentacion' => 'Cápsulas'],
            ],
            'Antihistamínicos' => [
                ['nombre' => 'Loratadina 10mg', 'laboratorio' => 'Bayer', 'concentracion' => '10mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Cetirizina 10mg', 'laboratorio' => 'Pfizer', 'concentracion' => '10mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Desloratadina 5mg', 'laboratorio' => 'Merck', 'concentracion' => '5mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Fexofenadina 120mg', 'laboratorio' => 'Sanofi', 'concentracion' => '120mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Difenhidramina 50mg', 'laboratorio' => 'Pfizer', 'concentracion' => '50mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Clorfeniramina 4mg', 'laboratorio' => 'Abbott', 'concentracion' => '4mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Hidroxizina 25mg', 'laboratorio' => 'Pfizer', 'concentracion' => '25mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Ebastina 10mg', 'laboratorio' => 'Almirall', 'concentracion' => '10mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Levocetirizina 5mg', 'laboratorio' => 'Glenmark', 'concentracion' => '5mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Bilastina 20mg', 'laboratorio' => 'Faes', 'concentracion' => '20mg', 'presentacion' => 'Tabletas'],
            ],
            'Digestivos' => [
                ['nombre' => 'Omeprazol 20mg', 'laboratorio' => 'AstraZeneca', 'concentracion' => '20mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Ranitidina 150mg', 'laboratorio' => 'GlaxoSmithKline', 'concentracion' => '150mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Metoclopramida 10mg', 'laboratorio' => 'Sanofi', 'concentracion' => '10mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Loperamida 2mg', 'laboratorio' => 'Janssen', 'concentracion' => '2mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Butilhioscina 10mg', 'laboratorio' => 'Boehringer', 'concentracion' => '10mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Esomeprazol 40mg', 'laboratorio' => 'AstraZeneca', 'concentracion' => '40mg', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Domperidona 10mg', 'laboratorio' => 'Janssen', 'concentracion' => '10mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Simeticona 125mg', 'laboratorio' => 'Bayer', 'concentracion' => '125mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Lactobacillus', 'laboratorio' => 'Biocodex', 'concentracion' => 'Variable', 'presentacion' => 'Cápsulas'],
                ['nombre' => 'Pantoprazol 40mg', 'laboratorio' => 'Takeda', 'concentracion' => '40mg', 'presentacion' => 'Tabletas'],
            ],
            'Respiratorios' => [
                ['nombre' => 'Ambroxol 30mg', 'laboratorio' => 'Boehringer', 'concentracion' => '30mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Salbutamol Inhalador', 'laboratorio' => 'GlaxoSmithKline', 'concentracion' => '100mcg', 'presentacion' => 'Inhalador'],
                ['nombre' => 'Loratadina + Pseudoefedrina', 'laboratorio' => 'Bayer', 'concentracion' => '5mg/120mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Bromhexina 8mg', 'laboratorio' => 'Boehringer', 'concentracion' => '8mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Montelukast 10mg', 'laboratorio' => 'Merck', 'concentracion' => '10mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Beclometasona Inhalador', 'laboratorio' => 'GlaxoSmithKline', 'concentracion' => '250mcg', 'presentacion' => 'Inhalador'],
                ['nombre' => 'Acetilcisteína 600mg', 'laboratorio' => 'Zambon', 'concentracion' => '600mg', 'presentacion' => 'Sobres'],
                ['nombre' => 'Teofilina 300mg', 'laboratorio' => 'Sanofi', 'concentracion' => '300mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Fluticasona Nasal', 'laboratorio' => 'GlaxoSmithKline', 'concentracion' => '50mcg', 'presentacion' => 'Spray nasal'],
                ['nombre' => 'Dextrometorfano 30mg', 'laboratorio' => 'Roche', 'concentracion' => '30mg', 'presentacion' => 'Jarabe'],
            ],
            'Dermatológicos' => [
                ['nombre' => 'Clotrimazol Crema 1%', 'laboratorio' => 'Bayer', 'concentracion' => '1%', 'presentacion' => 'Crema'],
                ['nombre' => 'Hidrocortisona Crema 1%', 'laboratorio' => 'Pfizer', 'concentracion' => '1%', 'presentacion' => 'Crema'],
                ['nombre' => 'Betametasona Crema 0.05%', 'laboratorio' => 'GlaxoSmithKline', 'concentracion' => '0.05%', 'presentacion' => 'Crema'],
                ['nombre' => 'Mupirocina Ungüento 2%', 'laboratorio' => 'GlaxoSmithKline', 'concentracion' => '2%', 'presentacion' => 'Ungüento'],
                ['nombre' => 'Ketoconazol Shampoo 2%', 'laboratorio' => 'Janssen', 'concentracion' => '2%', 'presentacion' => 'Shampoo'],
                ['nombre' => 'Tretinoína Crema 0.025%', 'laboratorio' => 'Janssen', 'concentracion' => '0.025%', 'presentacion' => 'Crema'],
                ['nombre' => 'Sulfato de Zinc Crema', 'laboratorio' => 'Bayer', 'concentracion' => '20%', 'presentacion' => 'Crema'],
                ['nombre' => 'Fusidato de Sodio Crema', 'laboratorio' => 'Leo Pharma', 'concentracion' => '2%', 'presentacion' => 'Crema'],
                ['nombre' => 'Aciclovir Crema 5%', 'laboratorio' => 'GlaxoSmithKline', 'concentracion' => '5%', 'presentacion' => 'Crema'],
                ['nombre' => 'Minoxidil Solución 5%', 'laboratorio' => 'Johnson & Johnson', 'concentracion' => '5%', 'presentacion' => 'Solución tópica'],
            ],
            'Cardiovasculares' => [
                ['nombre' => 'Losartán 50mg', 'laboratorio' => 'Merck', 'concentracion' => '50mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Enalapril 10mg', 'laboratorio' => 'Merck', 'concentracion' => '10mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Amlodipino 5mg', 'laboratorio' => 'Pfizer', 'concentracion' => '5mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Atorvastatina 20mg', 'laboratorio' => 'Pfizer', 'concentracion' => '20mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Metoprolol 50mg', 'laboratorio' => 'AstraZeneca', 'concentracion' => '50mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Carvedilol 25mg', 'laboratorio' => 'Roche', 'concentracion' => '25mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Furosemida 40mg', 'laboratorio' => 'Sanofi', 'concentracion' => '40mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Clopidogrel 75mg', 'laboratorio' => 'Sanofi', 'concentracion' => '75mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Digoxina 0.25mg', 'laboratorio' => 'GlaxoSmithKline', 'concentracion' => '0.25mg', 'presentacion' => 'Tabletas'],
                ['nombre' => 'Valsartán 80mg', 'laboratorio' => 'Novartis', 'concentracion' => '80mg', 'presentacion' => 'Tabletas'],
            ],
            'Material de Curación' => [
                ['nombre' => 'Gasas estériles 10x10cm', 'laboratorio' => '3M', 'concentracion' => 'N/A', 'presentacion' => 'Paquete x10'],
                ['nombre' => 'Vendas elásticas 10cm', 'laboratorio' => 'Johnson & Johnson', 'concentracion' => 'N/A', 'presentacion' => 'Rollo'],
                ['nombre' => 'Alcohol antiséptico 70%', 'laboratorio' => 'Medifarma', 'concentracion' => '70%', 'presentacion' => 'Frasco 500ml'],
                ['nombre' => 'Agua oxigenada 3%', 'laboratorio' => 'Medifarma', 'concentracion' => '3%', 'presentacion' => 'Frasco 250ml'],
                ['nombre' => 'Guantes de látex talla M', 'laboratorio' => 'Halyard', 'concentracion' => 'N/A', 'presentacion' => 'Caja x100'],
                ['nombre' => 'Jeringas 5ml', 'laboratorio' => 'BD', 'concentracion' => 'N/A', 'presentacion' => 'Unidad'],
                ['nombre' => 'Algodón hidrófilo 100g', 'laboratorio' => 'Medifarma', 'concentracion' => 'N/A', 'presentacion' => 'Paquete'],
                ['nombre' => 'Curitas adhesivas', 'laboratorio' => 'Johnson & Johnson', 'concentracion' => 'N/A', 'presentacion' => 'Caja x20'],
                ['nombre' => 'Esparadrapo 2.5cm x 5m', 'laboratorio' => '3M', 'concentracion' => 'N/A', 'presentacion' => 'Rollo'],
                ['nombre' => 'Termómetro digital', 'laboratorio' => 'Omron', 'concentracion' => 'N/A', 'presentacion' => 'Unidad'],
            ],
        ];

        $contador = 0;
        foreach ($categorias as $categoriaIndex => $categoria) {
            if (isset($productosData[$categoria->nombre])) {
                $productos = $productosData[$categoria->nombre];
                
                foreach ($productos as $index => $productoData) {
                    $contador++;
                    $stockActual = rand(10, 200);
                    $codigoUnico = '77' . str_pad($contador, 11, '0', STR_PAD_LEFT);
                    $skuCategoria = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $categoria->nombre), 0, 3));
                    
                    // Verificar si ya existe
                    $existe = Producto::where('nombre', $productoData['nombre'])
                        ->where('categoria_id', $categoria->id)
                        ->exists();
                    
                    if ($existe) {
                        continue;
                    }
                    
                    Producto::create([
                            'id' => Str::uuid(),
                            'nombre' => $productoData['nombre'],
                            'categoria_id' => $categoria->id,
                            'codigo_barras' => $codigoUnico,
                            'sku' => 'PRD-' . $skuCategoria . '-' . str_pad($contador, 6, '0', STR_PAD_LEFT),
                            'codigo_interno' => 'INT-' . str_pad($contador, 4, '0', STR_PAD_LEFT),
                            'laboratorio' => $productoData['laboratorio'],
                            'forma_farmaceutica' => in_array($productoData['presentacion'], ['Tabletas', 'Cápsulas', 'Cápsulas blandas']) ? $productoData['presentacion'] : 'Otro',
                            'concentracion' => $productoData['concentracion'],
                            'presentacion' => $productoData['presentacion'],
                            'via_administracion' => $categoria->nombre === 'Material de Curación' ? 'Tópica' : 'Oral',
                            'unidad_medida' => in_array($productoData['presentacion'], ['Tabletas', 'Cápsulas', 'Cápsulas blandas']) ? 'Unidad' : 'Caja',
                            'fracciones_por_unidad' => in_array($productoData['presentacion'], ['Tabletas', 'Cápsulas']) ? rand(10, 30) : 1,
                            'permite_fraccionar' => in_array($productoData['presentacion'], ['Tabletas', 'Cápsulas']),
                            'lote' => 'LOTE-' . strtoupper(Str::random(6)),
                            'fecha_vencimiento' => now()->addMonths(rand(6, 36)),
                            'registro_sanitario' => 'RS-' . rand(100000, 999999),
                            'refrigeracion_requerida' => rand(0, 10) < 2, // 20% requiere refrigeración
                            'dias_para_alertar_vencimiento' => 90,
                            'stock_actual' => $stockActual,
                            'stock_minimo' => 10,
                            'stock_maximo' => 500,
                            'precio_compra' => rand(5, 100) + (rand(0, 99) / 100),
                            'precio_venta' => rand(10, 150) + (rand(0, 99) / 100),
                            'margen_sugerido' => rand(15, 50),
                            'impuesto' => 16,
                            'etiquetas' => json_encode(['popular', 'stock-disponible']),
                            'fotos' => json_encode([]),
                            'descripcion' => 'Producto farmacéutico de alta calidad para el tratamiento médico.',
                            'estado' => 'activo',
                        ]);
                }
            }
        }

        $this->command->info('Productos seed completado correctamente. Se crearon 100 productos.');
    }
}
