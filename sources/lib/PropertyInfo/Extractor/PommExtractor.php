<?php
/*
 * This file is part of Pomm's SymfonyBidge package.
 *
 * (c) 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\SymfonyBridge\PropertyInfo\Extractor;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\Session;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Extract data using pomm.
 *
 * @package PommSymfonyBridge
 * @copyright 2015 Grégoire HUBERT
 * @author Nicolas Joseph
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PommExtractor implements PropertyTypeExtractorInterface
{
    private $pomm;

    public function __construct(Pomm $pomm)
    {
        $this->pomm = $pomm;
    }

    public function getTypes($class, $property, array $context = array())
    {
        if (isset($context['session:name'])) {
            $session = $this->pomm->getSession($context['session:name']);
        } else {
            $session = $this->pomm->getDefaultSession();
        }

        if (isset($context['model:name'])) {
            $modelName = $context['model:name'];
        } else {
            $modelName = "${class}Model";
        }

        $sqlType = $this->getSqlType($session, $modelName, $property);
        $pommType = $this->getPommType($session, $sqlType);

        return $this->createPropertyType($pommType);
    }

    private function getSqlType(Session $session, $modelName, $property)
    {
        $model = $session->getModel($modelName);
        $structure = $model->getStructure();

        return $structure->getTypeFor($property);
    }

    private function getPommType(Session $session, $sqlType)
    {
        $pommTypes = $session->getPoolerForType('converter')
            ->getConverterHolder()
            ->getTypesWithConverterName();

        if (!isset($pommTypes[$sqlType])) {
            throw new \RuntimeException("Invalid $sqltype");
        }

        return $pommTypes[$sqlType];
    }

    private function createPropertyType($pommType)
    {
        $class = null;
        $nullable = false;

        switch ($pommType) {
            case 'Array':
            case 'Boolean':
            case 'String':
                $type = strtolower($pommType);
                break;
            case 'Number':
                $type = Type::BUILTIN_TYPE_INT;
                break;
            case 'JSON':
                $type = Type::BUILTIN_TYPE_ARRAY;
                break;
            case 'Binary':
                $type = Type::BUILTIN_TYPE_STRING;
                break;
            case 'Timestamp':
                $type = Type::BUILTIN_TYPE_OBJECT;
                $name = \DateTime::class;
                break;
            case 'Interval':
                $type = Type::BUILTIN_TYPE_OBJECT;
                $name = \DateInterval::class;
                break;
            case 'Point':
                $type = Type::BUILTIN_TYPE_OBJECT;
                $name = \PommProject\Foundation\Converter\Type\Point::class;
                break;
            case 'Circle':
                $type = Type::BUILTIN_TYPE_OBJECT;
                $name = \PommProject\Foundation\Converter\Type\Circle::class;
                break;
            case 'NumberRange':
                $type = Type::BUILTIN_TYPE_OBJECT;
                $name = \PommProject\Foundation\Converter\Type\NumRange::class;
                break;
            case 'TsRange':
                $type = Type::BUILTIN_TYPE_OBJECT;
                $name = \PommProject\Foundation\Converter\Type\TsRange::class;
                break;
            default:
                $type = Type::BUILTIN_TYPE_OBJECT;
                $name = $pommType;
                break;
        }

        return new Type($type, $nullable, $class);
    }
}
