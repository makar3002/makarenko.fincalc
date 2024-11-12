<?php
namespace makarenko\fincalc\reports\control\frc;


use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Exception;
use makarenko\fincalc\reports\control\IblockElementRepository;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\IblockElement;


/**
 * Class FrcRepository - репозиторий для ЦФО.
 * @package makarenko\fincalc\reports\control
 */
class FrcService {
    /** @var int - неопределенный цвет ЦФО. */
    private const FRC_UNDEFINED_COLOR = 0;
    /** @var int - зеленый цвет ЦФО. */
    private const FRC_GREEN_COLOR = 1;
    /** @var int - красный цвет ЦФО. */
    private const FRC_RED_COLOR = 2;
    /** @var array - маппинг цветов ЦФО. */
    private const FRC_COLOR_MAP = array(
            'green' => FrcService::FRC_GREEN_COLOR,
            'red' => FrcService::FRC_RED_COLOR
    );

    /** @var IblockElementRepository */
    private $frcRepository;
    /** @var FrcMapper */
    private $frcMapper;

    /**
     * FrcRepository constructor.
     *
     * @param IblockElementRepository|null $frcRepository
     * @param FrcMapper|null $frcMapper
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    public function __construct(
            ?IblockElementRepository $frcRepository = null,
            ?FrcMapper $frcMapper = null
    ) {
        $frcIblockId = intval(Option::get('makarenko.fincalc', 'FINCALC_FRC_IBLOCK_ID'));
        $this->frcRepository = $frcRepository ?: new IblockElementRepository($frcIblockId);
        $this->frcMapper = $frcMapper ?: new FrcMapper();
    }

    /**
     * Возвращает массив корневых ЦФО.
     *
     * @return Frc[]
     *
     * @throws Exception
     */
    public function getRootFrcTreeList(): array {
        $notPreparedFrcList = $this->frcRepository->getIblockElementData(
                FrcMapper::FRC_FIELD_NAMES,
                FrcMapper::FRC_PROPERTY_NAMES
        );

        return $this->prepareFrcTreeList($notPreparedFrcList);
    }

    /**
     * Рекурсивно формирует и возвращает массив со структурой ЦФО (лес).
     *
     * @param IblockElement[] $frcList - массив объектов ЦФО из инфоблоков.
     * @param int|null $parentFrc - id ЦФО, для которого нужно сформировать массив дочерних FRC;
     * если не указан, то формирует структуру корневых ЦФО.
     * @param int $color - цвет ЦФО, которые используются для формирования структуры первого уровня.
     *
     * @return Frc[] - массив FRC.
     *
     * @throws Exception
     */
    private function prepareFrcTreeList(array $frcList, ?int $parentFrc = null, int $color = FrcService::FRC_UNDEFINED_COLOR): array {
        $rootFrcList = array_filter($frcList, function (IblockElement $frc) use ($parentFrc) {
            return $frc->getProperties()[FrcMapper::PARENT_FRC_FIELD_NAME] == $parentFrc;
        });

        if (in_array($color, FrcService::FRC_COLOR_MAP)) {
            $colorPropertyMap = $this->frcMapper->getColorPropertyMap();

            $rootFrcList = array_filter($rootFrcList, function ($frc) use ($color, $colorPropertyMap) {
                $colorValue = $colorPropertyMap[$frc->getProperties()[FrcMapper::COLOR_FIELD_NAME]];
                $frcColor = FrcService::FRC_COLOR_MAP[$colorValue];
                return $frcColor == $color;
            });
        }


        $nonRootFrcList = array_filter($frcList, function (IblockElement $frc) use ($parentFrc) {
            $currentParentFrc = $frc->getProperties()[FrcMapper::PARENT_FRC_FIELD_NAME];
            return !!$currentParentFrc && $currentParentFrc != $parentFrc;
        });

        $frcTreeNode = array();
        foreach ($rootFrcList as $frcId => $frc) {
            $frcTreeNode[$frcId] = $this->frcMapper->mapFrc(
                    $frc,
                    $this->prepareFrcTreeList($nonRootFrcList, $frcId, FrcService::FRC_GREEN_COLOR),
                    $this->prepareFrcTreeList($nonRootFrcList, $frcId, FrcService::FRC_RED_COLOR)
            );
        }

        return $frcTreeNode;
    }
}