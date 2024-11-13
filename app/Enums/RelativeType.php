<?php

namespace App\Enums;

enum RelativeType: int
{
    //Parents
    case father = 1;
    case mother = 2;
    case fatherInLaw = 3;
    case motherInLaw = 4;

    //Children
    case boy = 6;
    case daughter = 7;

    //Spouse
    case spouse = 5;

    //Siblings
    case sister = 8;
    case brother = 9;
    case sisterInLaw_wifeOfBrother = 10;
    case brotherInLaw_husbandOfSister = 11;
    case sisterInLaw_sisterOfSpouse = 21;
    case brotherInLaw_brotherOfSpouse = 22;
    case sisterInLaw_ofSpouse = 23;
    case brotherInLaw_ofSpouse = 24;

    //Grandparents
    case grandFather_fatherOfFather = 20;
    case grandFather_fatherOfMother = 12;
    case grandFather_fatherOfFatherInLaw = 13;
    case grandFather_fatherOfMotherInLaw = 14;
    case grandMother_motherOfFather = 15;
    case grandMother_motherOfMother = 16;
    case grandMother_motherOfFatherInLaw = 17;
    case grandMother_motherOfMotherInLaw = 18;

    //Uncle and aunt
    case uncle_brotherOfFather = 25;
    case uncle_brotherOfMother = 26;
    case uncle_brotherOfFatherInLaw = 27;
    case uncle_brotherOfMotherInLaw = 28;
    case aunt_sisterOfFather = 29;
    case aunt_sisterOfMother = 30;
    case aunt_sisterOfFatherInLaw = 31;
    case aunt_sisterOfMotherInLaw = 32;
    case aunt_wifeOfUncle_brotherOfFather = 33;
    case aunt_wifeOfUncle_brotherOfMother = 34;
    case aunt_wifeOfUncle_brotherOfFatherInLaw = 35;
    case aunt_wifeOfUncle_brotherOfMotherInLaw = 36;
    case uncle_husbandOfAunt_sisterOfFather = 37;
    case uncle_husbandOfAunt_sisterOfMother = 38;
    case uncle_husbandOfAunt_sisterOfFatherInLaw = 39;
    case uncle_husbandOfAunt_sisterOfMotherInLaw = 40;
}
